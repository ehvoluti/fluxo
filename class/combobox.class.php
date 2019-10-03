<?php
require_once("websac/require_file.php");
require_file("def/function.php");
require_file("class/connection.class.php");

if(!isset($_SESSION)){
	session_start();
}

class ComboBox{

	static function draw($cbname, $id = NULL, $attributes = NULL, $value = NULL, $filter = NULL, $optionnull = FALSE, $attroptioncreate = NULL){
		global $con;
//		$con->query("SET NAMES 'utf8'");
		if(!is_object($con)){
			$con = new Connection;
		}
		if(strpos($attributes, "parentId=") !== FALSE){
			$filter = "0=1";
		}
		switch($cbname){
// A
			case "administradora":
				$query = "SELECT codadminist, nome FROM administradora".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Administradora", "Administradora");
				break;
			case "ajusteproduto_custopreco":
				$query = self::fixed_values(array("1" => "Custo zerado", "2" => "Pre&ccedil;o zerado varejo", "3" => "Pre&ccedil;o zerado atacado", "4" => "Custo > Pre&ccedil;o", "5" => "Pre&ccedil;o em oferta", "6" => "Varejo < Sugest&atilde;o"));
				break;
			case "ajusteproduto_tipodescricao":
				$query = self::fixed_values(array("1" => "Qualquer parte", "2" => "Do in&iacute;cio"));
				break;
			case "ajusteproduto_tipoperc":
				$query = self::fixed_values(array("A" => "Aumentar", "D" => "Diminuir"));
				break;
			case "ano":
				$arr = array();
				$date = date("Y");
				for($i = date("Y") - 60; $date > $i; $date--){
					$arr[$date] = $date;
				}
				$query = self::fixed_values(($arr));
				break;
			case "atividade":
				$query = "SELECT codatividade, descricao FROM atividade".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Atividade", "Atividade");
				break;
// B
			case "balanca":
				$query = "SELECT codbalanca, nome FROM balanca".self::filter($filter)." ORDER BY nome";
				break;
			case "balanca_pluean":
				$query = self::fixed_values(array("P" => "PLU", "E" => "EAN", "4" => "EAN 4"));
				break;
			case "banco":
				$query = "SELECT codbanco, nome FROM banco WHERE codbanco in (SELECT codbanco from bancoestab where disponivel = 'S' AND codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')) ".(strlen($filter) > 0 ? " AND ".$filter : "" )." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadBanco", "Banco")." ajaxFile=\"banco\" parentField=\"codestabelec\" ";
				break;
			case "banco_semrestricao":
				$query = "SELECT codbanco, nome FROM banco ".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadBanco", "Banco")." ajaxFile=\"banco\" parentField=\"codestabelec\" ";
				break;
			case "banco_codoficial":
				if(strlen($filter) > 0){
					$filter .= " AND ";
				}
				$filter .= "codoficial IS NOT NULL";
				$query = "SELECT codbanco, codoficial FROM banco".self::filter($filter)." ORDER BY codoficial";
				$attributes = self::idtable($attributes, "CadBanco", "Banco");
				break;
			case "bloconodre":
				$query = self::fixed_values(array("1" => "1-Vendas", "2" => "2-Devolu&ccedil;&atilde;o de Venda", "3" => "3-Compras", "4" => "4-Devolu&ccedil;&atilde;o de Compra", "5"=> "5-Impostos", "6" => "6-Outras Receitas", "7" => "7-Despesas Operacionais Fixas", "8" => "8-Despesas Operacionais Variaveis"));
				break;
// C
			case "cadastro_origem":
				$query = self::fixed_values(array("A" => "Ativo", "O" => "Outros", "P" => "Ponto de Venda", "E" => "Ecommerce", "T" => "Telefone"));
				break;
			case "caixa":
				$query = "SELECT DISTINCT (caixa) codecf, 'CAIXA ' || caixa as descricao FROM ecf ".self::filter($filter)." AND status= 'A' ORDER BY descricao";
				$attributes = self::idtable($attributes, "Caixa", "Caixa")." ajaxFile=\"caixa\" parentField=\"codestabelec\" ";
				break;
			case "carimbo":
				$query = "SELECT codcarimbo, descrreduzida FROM carimbo".self::filter($filter)." ORDER BY descrreduzida";
				break;
			case "cartao":
				$query = "SELECT codcartao, descricao FROM cartao".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Cartao", "Cartao");
				break;
			case "cartao_tpcartao":
				$query = self::fixed_values(array("A" => "Alimenta&ccedil;&atilde;o", "C" => "Conv&ecirc;nio", "F" => "Fidelidade", "H" => "Cheque"));
				break;
			case "catlancto":
				$query = "SELECT codcatlancto, descricao FROM catlancto".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadCatLancto", "Categoria de Lan&ccedil;amentos")." onChange=\"filterchild(this.id)\" ";
				break;
			case "catrestricao":
				$query = "SELECT codcatrestricao, descricao FROM catrestricao".self::filter($filter)." ORDER BY descricao";
				break;
			case "centrocusto":
				$query = "SELECT codccusto, nome FROM centrocusto".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadCentroCusto", "Centro de Custo");
				break;
			case "cidade":
				$query = "SELECT codcidade, nome FROM cidade".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Cidade", "Cidade")." ajaxFile=\"cidade\" parentField=\"uf\" ";
				break;
			case "clicartaoprop_status":
				$query = self::fixed_values(array("A" => "Ativo", "B" => "Bloqueio Autom&aacute;tico", "M" => "Bloqueio Manual", "C" => "Cancelado", "I" => "Inativo"));
				break;
			case "classfiscal":
				$query = "SELECT codcf, descricao FROM classfiscal".self::filter($filter)." ORDER BY tptribicms, aliqicms, aliqredicms, aliqiva, valorpauta, aliqii";
				$attributes = self::idtable($attributes, "CFiscal", "Classifica&ccedil;&atilde;o Fiscal");
				break;
			case "classificacao":
				$query = "SELECT codclassif, descricao FROM classificacao".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Classif", "Classifica&ccedil;&atilde;o");
				break;
			case "classificacao_tipo":
				$query = self::fixed_values(array("C" => "Cliente", "F" => "Fornecedor", "T" => "Transportadora", "U" => "Funcion&aacute;rio"), TRUE);
				break;
			case "cliente":
				$query = "SELECT codcliente, nome FROM cliente ".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "ClientePF", "Clientes");
				break;
			case "cliente_tppessoa":
				$query = self::fixed_values(array("F" => "F&iacute;sica", "J" => "Jur&iacute;dica"));
				break;
			case "cliente_convenio":
				$query = "SELECT codcliente, nome FROM cliente WHERE convenio = 'S' ORDER BY nome";
				$attributes = self::idtable($attributes, "ClientePF", "Clientes");
				break;
			case "codigocarteira":
				$query = self::fixed_values(array("1" => "Cobrança Simples", "2" => "Cobrança Vinculada", "3" => "Cobrança Caucionada", "4" => "Cobrança Descontada","5" => "Cobrança Vendor"), TRUE);
				break;
			case "complcadastro_tabela":
				$query = self::fixed_values(array("cliente" => "Cliente", "produto" => "Produto", "fornecedor" => "Fornecedor"), TRUE);
				break;
			case "complcadastro_tipo":
				$query = self::fixed_values(array("B" => "L&oacute;gico", "D" => "Data", "F" => "Decimal", "I" => "Inteiro", "S" => "Texto", "T" => "Hora"), TRUE);
				break;
			case "codigoservico":
				$query = "SELECT idcodigoservico, idcodigoservico || '-' || descricao FROM codigoservico ORDER BY idcodigoservico";
				break;
			case "composicao":
				$query = "SELECT composicao.codcomposicao, produto.descricaofiscal FROM composicao INNER JOIN produto ON (composicao.codproduto = produto.codproduto) ".self::filter($filter)."ORDER BY produto.descricaofiscal";
				$attributes = self::idtable($attributes, "CadComposicao", "Composi&ccedil;&atilde;o");
				break;
			case "composicao_paifilho":
				$query = self::fixed_values(array("F" => "Pai", "P" => "Filho"));
				break;
			case "composicao_explosaoauto":
				$query = self::fixed_values(array("S" => "Autom&aacute;tica", "N" => "N&atilde;o explodir"));
				break;
			case "composicao_tipo":
				$query = self::fixed_values(array("V" => "Explode na Venda", "C" => "Explode na Compra", "A" => "Explode em Ambos", "D" => "Desmembramento", "P" => "Produ&ccedil;&atilde;o", "T" => "Explode na Transfer&ecirc;ncia"));
				break;
			case "composicao_tipopreco":
				$query = self::fixed_values(array("A" => "Autom&aacute;tico","O" => "Autom&aacute;tico com (oferta)", "M" => "Manual"));
				break;
			case "composicao_tipocusto":
				$query = self::fixed_values(array("S" => "Custo Completo do Pai", "P" => "Por Participa&ccedil;&atilde;o", "N" => "N&atilde;o Atualizar", "D" => "Dividido Automatico", "F" => "Fator de Venda"));
				break;
			case "concorrente":
				$query = "SELECT codconcorrente, nome FROM concorrente".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadConcorrente", "Concorrente");
				break;
			case "condpagto":
				/* OS 5240 */
				if(!is_null($attroptioncreate)){
					$attroption = self::preparaattroptioncreate($attroptioncreate);
				}
				$query = "SELECT codcondpagto, descricao {$attroption} FROM condpagto".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CondPagto", "Condi&ccedil;&atilde;o de Pagamento");
				break;
			case "condpagto_tipo":
				$query .= self::fixed_values(array("DD" => "Dias da data de emiss&atilde;o", "DDL" => "Dias da data de entrega", "PV" => "Parcelamento em Vezes", "RC" => "Parcelamento Recorrente"));
				break;
			case "conferenciafiscal_statuscontabil":
				$query = self::fixed_values(array("P" => "Pendente", "A" => "Aprovado", "R" => "Reprovado"));
				break;
			case "conslancamento_agrupamento":
				$query = self::fixed_values(array("01" => "Categoria", "02" => "SubCategoria"));
				break;
			case "contabilidade":
				$query = "SELECT codcontabilidade, nome FROM contabilidade".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadContabilidade", "Contabilidade");
				break;
			case "contagem":
				$query = self::fixed_values(array("1" => "Primeira", "2" => "Segunda"));
				break;
			case "contingencianfe_tipo":
				$query = self::fixed_values(array("3" => "SCAN", "5" => "FS-DA"));
				break;
			case "contribuicaosocial":
				$query = "SELECT codccs, codccs|| ' - ' || descricao FROM contribuicaosocial".self::filter($filter)." ORDER BY codccs";
				break;
			case "controleprocfinan":
				$query = "SELECT DISTINCT codcontrprocfinan,'Remessa Nr:'|| LPAD(CAST(codcontrprocfinan AS text),5,'0') || ' - Em:' || formatar(dataprocesso) || ' ' || date_trunc('seconds',horaprocesso) || ' - Por:' || controleprocfinan.usuario AS geracao FROM controleprocfinan INNER JOIN lancamento ON (controleprocfinan.codcontrprocfinan = lancamento.processogr) ".self::filter($filter)." ORDER BY codcontrprocfinan DESC";
				$attributes .= " ajaxFile=\"controleprocfinan\" parentField=\"pagrec\" ";
				break;
			case "cotacao":
				$query = "SELECT codcotacao, descricao FROM cotacao".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Cotacao", "Cota&ccedil;&atilde;o");
				break;
			case "cotacao_status":
				$query = self::fixed_values(array("A" => "Aberta", "E" => "Encerrada", "G" => "Pedidos Gerados", "S" => "Suspensa"), TRUE, $filter);
				break;
			case "cotacao_tiporank":
				$query = self::fixed_values(array("P" => "Produto", "F" => "Fornecedor", "D" => "Distribuido"));
				break;
			case "csosnicms":
				$query = "SELECT codcsosn, codcsosn || ' - ' || descricao FROM csosnicms".self::filter($filter)." ORDER BY codcsosn";
				break;
			case "csticms":
				$query = "SELECT codcst, codcst || ' - ' || descricao FROM csticms".self::filter($filter)." ORDER BY codcst";
				break;
			case "cstipient":
				$query = "SELECT codcst, codcst || ' - ' || descricao FROM cstipi".self::filter($filter)." ORDER BY codcst";
				break;
			case "cstipisai":
				$query = "SELECT codcst, codcst || ' - ' || descricao FROM cstipi".self::filter($filter)." ORDER BY codcst";
				break;
			case "cstpiscofins":
				$query = "SELECT codcst, codcst || ' - ' || descricao FROM cstpiscofins".self::filter($filter)." ORDER BY codcst";
				break;
			case "cupom_status":
				$query = self::fixed_values(array("A" => "Atendido", "C" => "Cancelado"));
				break;
			case "cupom_tiporelatorio":
				$query = self::fixed_values(array("1" => "Analitico","2" => "Sintético","3" => "Fiscal","4" => "Totalizado"));
				break;
			case "curvaabc":
				$query = self::fixed_values(array("A" => "A","B" => "B","C" => "C"));
				break;
// D
			case "debcred":
				$query = self::fixed_values(array("C" => "Cr&eacute;dito", "D" => "D&eacute;bito"));
				break;
			case "departamento":
				$query = "SELECT coddepto, nome FROM departamento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Depto", "Departamento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "departamento_restricao":
				$filter .= " coddepto NOT IN (SELECT coddepto FROM usuariodepartamento WHERE disponivel = 'N' AND login = '{$_SESSION["WUser"]}') ";

				$query = "SELECT coddepto, nome FROM departamento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Depto", "Departamento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "dia":
				$arr = array();
				for($i = 1; $i <= 31; $i++){
					$arr[$i] = $i;
				}
				$query = self::fixed_values($arr);
				break;
			case "dominio_tipogeracao":
				$query = self::fixed_values(array("N" => "Fiscal", "F" => "Financeiro"));
				break;
			case "dbtype":
				$query = self::fixed_values(array("pgsql" => "PostgreSQL", "mysql" => "MySQL", "firebird" => "Interbase/Firebird"));
				break;
			case "dre_tiporel":
				$query = self::fixed_values(array("caixa" => "Por Vencimento", "comp" => "Por Emissão"));
				break;
// E
			case "ecf":
				$query = "SELECT codecf, numfabricacao FROM ecf ".self::filter($filter)." ORDER BY numfabricacao";
				$attributes = self::idtable($attributes, "Ecf", "ECF")." ajaxFile=\"ecf\" parentField=\"codestabelec\" ";
				break;
			case "ecf_status":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo"));
				break;
			case "ecommerce_tematipo":
				$query = self::fixed_values(array("P" => "Padrão", "V" => "Vitrini","A" => "Atacadista"));
				break;
			case "ecommerce_carrossel_status":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo"));
				break;
			case "embalagem":
				$query = "SELECT embalagem.codembal, embalagem.descricao FROM embalagem INNER JOIN unidade USING (codunidade)".self::filter($filter)." ORDER BY unidade.descricao, embalagem.quantidade";
				$attributes = self::idtable($attributes, "Embalagem", "Embalagem");
				break;
			case "emitente":
				$query = "SELECT codemitente, nome FROM emitente".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Emitente", "Emitente");
				break;
			case "empresa":
				$query = "SELECT codcliente, nome FROM cliente WHERE convenio = 'S' ORDER BY nome";
				$attributes = self::idtable($attributes, "ClientePF", "Clientes");
				break;
			case "emitente_tipoemp":
				$query = self::fixed_values(array("A" => "Atacado", "I" => "Ind&uacute;stria", "V" => "Varejo"));
				break;
			case "equivalente":
				$query = "SELECT codequivalente, descricao FROM equivalente".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadEquivalente", "Equivalente");
				break;
			case "especie":
				/*OS 5240 - */
				if(!is_null($attroptioncreate)){
					$attr = self::preparaattroptioncreate($attroptioncreate);
				}
				$query = "SELECT codespecie, descricao {$attr} FROM especie".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Especie", "Forma de Pagamento");
				break;
			case "especie_especie":
				$query = self::fixed_values(array("BL" => "Boleto Banc&aacute;rio", "CC" => "Cart&atilde;o de Cr&eacute;dito", "CD" => "Cart&atilde;o de D&eacute;bito", "CH" => "Cheque", "CV" => "Conv&ecirc;nio", "DC" => "D&eacute;bito em Conta", "DH" => "Dinheiro", "DR" => "Dep&oacute;sito em Conta Corrente", "OT" => "Outros", "DP" => "Duplicata"), TRUE);
				break;
			case "especie_financtppessoa":
				$query = self::fixed_values(array("F" => "Pessoa F&iacute;sica", "J" => "Pessoa Jur&iacute;dica", "A" => "Ambas"));
				break;
			case "especie_limitecliente":
				$query = self::fixed_values(array("1" => "Limite 1", "2" => "Limite 2"));
				break;
			case "estabelecimento":
				$param = param("RESTRICAO", "USUAESTABEL", $con);
				if($param == "1"){
					if(strlen($filter) > 0){
						$filter = "(".$filter.") AND ";
					}
					$filter .= "codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')";
				}
				if(strlen($value) == 0){
					$usuaestabel = objectbytable("usuaestabel", NULL, $con);
					$usuaestabel->setlogin($_SESSION["WUser"]);
					$arr_usuaestabel = object_array($usuaestabel);
					if(count($arr_usuaestabel) == 1){
						$value = $arr_usuaestabel[0]->getcodestabelec();
					}
				}
				$query = "SELECT codestabelec, nome FROM estabelecimento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Estabel", "Estabelecimento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "estabelec_semrestricao":
				$query = "SELECT codestabelec, nome FROM estabelecimento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Estabel", "Estabelecimento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "estabelecimento_fiscal":
				$param = param("RESTRICAO", "USUAESTABEL", $con);
				if($param == "1"){
					if(strlen($filter) > 0){
						$filter = "(".$filter.") AND ";
					}
					$filter .= "codestabelecfiscal IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')";
				}
				if(strlen($value) == 0){
					$usuaestabel = objectbytable("usuaestabel", NULL, $con);
					$usuaestabel->setlogin($_SESSION["WUser"]);
					$arr_usuaestabel = object_array($usuaestabel);
					if(count($arr_usuaestabel) == 1){
						$value = $arr_usuaestabel[0]->getcodestabelec();
					}
				}
				$query = "SELECT DISTINCT codestabelecfiscal, (SELECT nome FROM estabelecimento estab WHERE estab.codestabelec = estabelecimento.codestabelecfiscal) AS nome FROM estabelecimento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Estabel", "Estabelecimento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "estabelecimento_finan":
				$param = param("RESTRICAO", "USUAESTABEL", $con);
				if($param == "1"){
					if(strlen($filter) > 0){
						$filter = "(".$filter.") AND ";
					}
					$filter .= "codestabelecfinan IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')";
				}
				if(strlen($value) == 0){
					$usuaestabel = objectbytable("usuaestabel", NULL, $con);
					$usuaestabel->setlogin($_SESSION["WUser"]);
					$arr_usuaestabel = object_array($usuaestabel);
					if(count($arr_usuaestabel) == 1){
						$value = $arr_usuaestabel[0]->getcodestabelec();
					}
				}
				$query = "SELECT DISTINCT codestabelecfinan, (SELECT nome FROM estabelecimento estab WHERE estab.codestabelec = estabelecimento.codestabelecfinan) AS nome FROM estabelecimento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Estabel", "Estabelecimento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "estabelecimento_equipamentofiscal":
				$query = self::fixed_values(array("ECF" => "ECF", "SAT" => "SAT","NFC" => "NFC-e"));
				break;
			case "estabelecimento_parceiro":
				$query = "SELECT codestabelec, nome FROM estabelecimento".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Estabel", "Estabelecimento")." onChange=\"filterchild(this.id)\" ";
				break;
			case "estabelecimento_perfil":
				$query = self::fixed_values(array("A" => "A", "B" => "B", "C" => "C"));
				break;
			case "estabelecimento_regimetributario":
				$query = self::fixed_values(array("1" => "Simples Nacional", "2" => "Lucro Presumido", "3" => "Lucro Real"));
				break;
			case "estabelecimento_tipoatividade":
				$query = self::fixed_values(array("V" => "Varejista", "A" => "Atacadista", "D" => "Industria", "I" => "Importadora"));
				break;
			case "estado":
				$query = "SELECT uf, nome FROM estado".self::filter($filter)." ORDER BY nome";
				$attributes .= " onChange=\"filterchild(this.id)\" ";
				break;
			case "estoque":
				$query = self::fixed_values(array("0" => "Com estoque", "1" => "Negativo", "2" => "Zerado", "3" => "M&aacute;ximo", "4" => "M&iacute;nimo"));
				break;
			case "etiqcliente":
				$query = "SELECT codetiqcliente, descricao FROM etiqcliente ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadEtiqCliente", "Etiqueta de Cliente");
				break;
			case "etiqgondola":
				$query = "SELECT codetiqgondola, descricao FROM etiqgondola WHERE codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."') ".(strlen($filter) > 0 ? " AND ".$filter : "" )."  ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadEtiqGondola", "Etiqueta de G&ocirc;ndola");
				break;
			case "etiqnotafiscal":
				$query = "SELECT codetiqnotafiscal, descricao FROM etiqnotafiscal ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadEtiqNotaFiscal", "Etiqueta de Nota Fiscal");
				break;
			case "etiqgondola_grauorientacao":
				$query = self::fixed_values(array("0" => "0&deg;", "1" => "90&deg;", "2" => "180&deg;", "3" => "270&deg;"));
				break;
			case "etiqgondola_orientacao":
				$query = self::fixed_values(array("R" => "Retrato", "P" => "Paisagem"));
				break;
			case "etiqgondola_qtdeetiqnf":
				$query = self::fixed_values(array("U" => "Unitário", "N" => "Nota Fiscal"));
				break;
			case "etiqgondola_tipodescricao":
				$query = self::fixed_values(array("N" => "Normal", "R" => "Reduzida"));
				break;
			case "etiqgondola_tipo_infonutricional":
				$query = self::fixed_values(array("0" => "Texto", "1" => "Tabela"));
				break;
			case "evento_nfe":
				$query = self::fixed_values(array("210210" => "Ciência da Operação", "210200" => "Confirma Operação", "210220" => "Desconhece Operação", "210240" => "Operação não Realizada"));
				break;
			case "exportapdv_registro":
				$query = self::fixed_values(array("todos" => "Todos", "produto" => "Produto", "departamento" => "Departamento", "composicao" => "Composi&ccedil;&atilde;o", "cliente" => "Cliente", "funcionario" => "Funcion&aacute;rio"));
				break;
// F
			case "familia":
				$query = "SELECT codfamilia, descricao FROM familia".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadFamilia", "Fam&iacute;lia de Produtos");
				break;
			case "familiafornec":
				$query = "SELECT codfamfornec, descricao FROM familiafornec".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadFamFornec", "Fam&iacute;lia de Fornecedores");
				break;
			case "finalizadora_tipogeracao":
				$query = self::fixed_values(array("A" => "Anal&iacute;tico", "S" => "Sint&eacute;tico"));
				break;
			case "finalizadora":
				$query = "SELECT codfinaliz, codestabelec||' - '||descricao FROM finalizadora".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Finalizadora", "Finalizadora");
				break;
			case "finalizadoraestabelec":
				$query = "SELECT codfinaliz, codestabelec||' - '||descricao FROM finalizadora".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Finalizadora", "Finalizadora")." ajaxFile=\"finalizadora\" parentField=\"codestabelec\" ";
				break;
			case "fixoperc":
				$query = self::fixed_values(array("F" => "Fixo", "P" => "Percentual"));
				break;
			case "fontetv":
				$query = self::fixed_values(array("Arial" => "Arial", "Verdana" => "Verdana", "Helvetica"=>"Helvetica", "Tahoma" => "Tahoma", "Calibri" => "Calibri"));
				break;
			case "formato_impressao":
				$query = self::fixed_values(array("L" => "Paisagem", "P" => "Retrato"));
				break;
			case "fornecedor":
				$query = "SELECT codfornec, nome FROM fornecedor".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Fornecedor", "Fornecedor");
				break;
			case "fornecedor_modosubsttrib":
				$query = self::fixed_values(array("0" => "Estado do fornecedor conveniado, com substitui&ccedil;&atilde;o tribut&aacute;ria destacada na nota fiscal", "1" => "Destinat&aacute;rio paga a guia GNRE", "2" => "Guia paga pelo fornecedor e cobrada junto com a nota fiscal"));
				break;
			case "fornecedor_status":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo"));
				break;
			case "fornecedor_tipocompra":
				$query = self::fixed_values(array("0" => "Normal", "1" => "Apenas por cota&ccedil;&atilde;o", "2" => "Sem cota&ccedil;&atilde;o"));
				break;
			case "fornecedor_tipocotacao":
				$query = self::fixed_values(array("E" => "Por Embalagem", "U" => "Por Unidade"));
				break;
			case "frentecaixa":
				$query = "SELECT codfrentecaixa, nome FROM frentecaixa".self::filter($filter)." ORDER BY nome";
				break;
			case "frentecaixa_tipocodproduto":
				$query = self::fixed_values(array("P" => "Produto", "E" => "EAN"));
				break;
			case "frentecaixa_tipodescricao":
				$query = self::fixed_values(array("C" => "Completa", "R" => "Resumida", "A" => "Ambos"));
				break;
			case "funcmeta_tipo":
				$query = self::fixed_values(array("F" => "Funcionarios", "C" => "Classifica&ccedil;&otilde;es"));
				break;
			case "funcionario":
				/* if($filter != "0=1"){
				  $filter = "situacao != 'I'";
				  } */
				$query = "SELECT codfunc, nome FROM funcionario".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadFuncionario", "Funcion&aacute;rio")." ajaxFile=\"funcionario\" parentField=\"codestabelec\" ";
				break;
			case "funcionario_situacao":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo", "F" => "F&eacute;rias", "L" => "Licen&ccedil;a"));
				break;
			case "finalidade":
				$query = self::fixed_values(array("2" => "Complemento", "3" => "Ajuste"));
				break;
// G
			case "gerabalanca_tiposetor":
				$query = self::fixed_values(array("0" => "Geral", "1" => "Departamentos Cadastrados", "2" => "Grupos Cadastrados"));
				break;
			case "grupo":
				$query = "SELECT codgrupo, nome FROM grupo".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "GrupoUsuario", "Grupo de Usu&aacute;rios");
				break;
			case "grupoocorrencia":
				$query = "SELECT codgrupoocor, descricao FROM grupoocorrencia".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadGrupoOcorrencia", "Grupo de Ocorr&ecirc;ncias");
				break;
			case "grupocta":
				$query = "SELECT codgrcta, descricao FROM grupocta".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadGrupoCta", "Grupo de Contas");
				break;
			case "grupofatmto":
				$query = "SELECT codgrfat, descricao FROM grupofatmto".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "GrupoFatmto", "Grupo de Faturamento");
				break;
			case "gruponatoperacao":
				$query = "SELECT siglagruponatoper, descricao FROM gruponatoperacao".self::filter($filter)." ORDER BY descricao";
				break;
			case "grupoprod":
				$query = "SELECT codgrupo, descricao FROM grupoprod".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Grupo", "Grupo de Produtos")." onchange=\"filterchild(this.id)\" ajaxFile=\"grupoprod\" parentField=\"coddepto\" ";
				break;
// H
			case "historico_tabela":
				$query = "SELECT tablename,tablename FROM pg_tables WHERE schemaname = 'public' order by 1";
				break;
			case "historicopadrao":
				$query = "SELECT codhistorico, descricao FROM historicopadrao".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadHistoricoPadrao", "Hist&oacute;rico Padr&atilde;o");
				break;
// I
			case "impressora":
				$query = "SELECT codimpressora, nome FROM impressora".self::filter($filter)." ORDER BY nome";
				break;
			case "impetiqordem":
				$query = self::fixed_values(array("departamento" => "Departamento", "descricao" => "Descri&ccedil;&atilde;o"));
				break;
			case "imptipoprecoof":
				$query = self::fixed_values(array("N" => "Normal","A" => "Atacado", "O" => "Oferta (todas altera&ccedil;&otilde;es)", "V" => "Oferta com valor"));
				break;
			case "indicadoremitente":
				$query = self::fixed_values(array("0" => "Emissão de Terceiro", "1" => "Emissão Própria"));
				break;
			case "indicadoroperacao":
				$query = self::fixed_values(array("0" => "Serviço Contratado Pelo Estabelecimento", "1" => "Serviço Prestado Pelo Estabelecimento"));
				break;
			case "indicadorpagamento":
				$query = self::fixed_values(array("0" => "À Vista", "1" => "A Prazo", "9" => "Sem Pagamento"));
				break;
			case "indicadororigemcredito":
				$query = self::fixed_values(array("0" => "0 - Operação no Mercado Interno", "1" => "1 - Operação de Importação"));
				break;
			case "interesse":
				$query = "SELECT codinteresse, descricao FROM interesse".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadInteresse", "Interesse");
				break;
			case "inventario":
				$query = "SELECT codinventario, descricao FROM inventario".self::filter($filter)." ORDER BY data DESC";
				$attributes = self::idtable($attributes, "Inventario", "Invent&aacute;rio")." ajaxFile=\"inventario\" parentField=\"codestabelec\" ";
				break;
			case "inventario_layout":
				$query = "SELECT codinventario, descricao FROM inventario".self::filter($filter)." ORDER BY descricao ASC";
				$attributes = self::idtable($attributes, "Inventario", "Invent&aacute;rio")." ajaxFile=\"inventario\" parentField=\"codestabelec\" ";
				break;
			case "inventario_contagens":
				$query = self::fixed_values(array("1" => "1", "2" => "2"));
				break;
			case "inventario_tipofiltro":
				$query = self::fixed_values(array("TOD" => "Todos os produtos", "FOR" => "Fornecedor", "DEP" => "Departamento", "GRU" => "Grupo", "SUB" => "SubGrupo"));
				break;
			case "instrucaobancaria":
				$query = "SELECT codigoinstrucao, instrucao || ' - ' || descricao FROM instrucaobancaria ".self::filter($filter)." ORDER BY instrucao";
				$attributes = $attributes." ajaxFile=\"instrucaobancaria\" parentField=\"codoficial\" ";
				break;
			case "ipi":
				$query = "SELECT codipi, descricao FROM ipi ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadIpi", "IPI");
				break;
			case "itpedido_tipopesquisa":
				$query = self::fixed_values(array("PLU" => "PLU", "REF" => "Ref Forn"));
				break;
// J
// K
// L
			case "tema":
				$query = self::fixed_values(array("1" => "Padrão"));
				break;
			case "lancamento_data":
				$query = self::fixed_values(array("R" => "Reconcilia&ccedil;&atilde;o", "L" => "Liquida&ccedil;&atilde;o"));
				break;
			case "lancamento_ocorrencia":
				$query = "SELECT DISTINCT ocorrencia, ocorrencia FROM lancamento ".self::filter($filter)." ORDER BY ocorrencia";
				break;
			case "lancamento_pagrec":
				$query = self::fixed_values(array("P" => "Pagamento", "R" => "Recebimento"));
				$attributes .= " onchange=\"filterchild(this.id)\"";
				break;
			case "lancamento_prevreal":
				$query = self::fixed_values(array("P" => "Previs&atilde;o", "R" => "Real"));
				break;
			case "lancamento_status":
				$query = self::fixed_values(array("A" => "Em Aberto", "L" => "Liquidado", "R" => "Reconciliado", "B" => "Bloqueado"));
				break;
			case "layout":
				$query = "SELECT codlayout, descricao FROM layout ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadLayout", "Layout");
				break;
			case "layout_alinhamento":
				$query = self::fixed_values(array("L" => "Esquerda", "R" => "Direita"));
				break;
			case "layout_autointervalo":
				$query = self::fixed_values(array("0" => "Desligado", "1" => "A cada 1 hora", "2" => "A cada 2 horas", "4" => "A cada 4 horas", "6" => "A cada 6 horas", "12" => "A cada 12 horas", "24" => "A cada 24 horas"));
				break;
			case "layout_ordem":
				$query = self::fixed_values(array("1" => "C&oacute;digo do Estabelecimento", "2" => "C&oacute;digo do Produto", "3" => "Descri&ccedil;&atilde;o Produto",
							"4" => "Descri&ccedil;&atilde;o Reduzida Produto", "13" => "C&oacute;digo do EAN", "5" => "Custo", "8" => "Pre&ccedil;o Atacado", "9" => "Pre&ccedil;o Varejo",
							"6" => "Pre&ccedil;o Varejo Oferta", "10" => "Posi&ccedil;&atilde;o de Estoque"));
				break;
			case "layoutremessabancaria":
				$query = self::fixed_values(array("240" => "CNAB 240", "400" => "CNAB 400"));
				break;
			case "layout_tipolayout":
				$query = self::fixed_values(array("E" => "Exporta&ccedil;&atilde;o", "I" => "Importa&ccedil;&atilde;o"));
				break;
			case "layout_tipotabulacao":
				$query = self::fixed_values(array("C" => "Por caractere", "P" => "Por posi&ccedil;&atilde;o"));
				break;
			case "login":
				$query = "SELECT DISTINCT login AS codlogin, login FROM historico ORDER BY login";
				$attributes = self::idtable($attributes, "codlogin", "login");
				break;
// M
			case "maiormenorque":
				$query = self::fixed_values(array(">" => "Maior que", "<" => "Menor que"));
				break;
			case "marca":
				$query = "SELECT codmarca, descricao FROM marca".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadMarca", "Marca");
				break;
			case "mercadologico":
				$query = "SELECT idmercadologico, descricao FROM mercadologico ".self::filter($filter)." ORDER BY descricao";
				$attributes = implode(" ", array(
					self::idtable($attributes, "Mercadologico", "Mercadológico"),
					"onChange='filterchild(this.id)'",
					"ajaxFile='mercadologico'",
					"parentField='idmercadologicopai'"
				));
				break;
			case "mes":
				$query = self::fixed_values(array("1" => "Janeiro", "2" => "Fevereiro", "3" => "Mar&ccedil;o", "4" => "Abril", "5" => "Maio", "6" => "Junho", "7" => "Julho", "8" => "Agosto", "9" => "Setembro", "10" => "Outubro", "11" => "Novembro", "12" => "Dezembro"));
				break;
			case "mixfiscal_tipocontribuinte":
				$query = self::fixed_values(array("sac" => "Atacado para Contribuinte", "sas" => "Simples Nacional", "svc" => "Varejo para Contribuinte", "snc" => "Atacado ou Varejo para n&atilde;o Contribuinte"));
				break;
			case "modeloemail":
				$query = "SELECT codmodeloemail, descricao FROM (SELECT * FROM modeloemail WHERE status != 'I') tmp ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadModeloEmail", "Modelo de E-mail");
				break;
			case "modeloemail_tipoautenticacao":
				$query = self::fixed_values(array("none" => "NONE", "ssl" => "SSL", "tls" => "TLS"));
				break;
			case "modeloemail_status":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo"));
				break;
			case "modeloemail_ativarautenticacao":
				$query = self::fixed_values(array("1" => "Sim", "2" => "N&atilde;O"));
				break;
			case "modeloemail_protocoloemail":
				$query = self::fixed_values(array("sendmail" => "SENDMAIL", "smtp" => "SMTP"));
				break;
			case "modeloemail_tiposervidoremail":
				$query = self::fixed_values(array("1" => "SMTP", "2" => "IMAP"));
				break;
			case "modelosaneamento":
				$query = "SELECT codmodelo, descricao FROM modelosaneamento ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadModeloSaneamento", "Modelo de Saneamento");
				break;
			case "moeda":
				$query = "SELECT codmoeda, descricao FROM moeda".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadMoeda", "Moeda");
				break;
			case "motivodesoneracaoicms":
				$query = self::fixed_values(array("1" => "1-Taxi", "2" => "2-Deficiente Fisico", "3" => "3-Produtor Agropecuario","4" => "4-Frotista/Locadora","5" => "5-Diplomatico/Consular", "6" => "6-Utilitários e Motocicletas da Amazônia Ocidental e Áreas de Livre Comércio", "7" => "7-Suframa", "9" => "9-Outros"));
				break;
			case "movimento_tipo":
				$query = self::fixed_values(array("E" => "Entrada", "S" => "Sa&iacute;da"));
				break;
			case "movimentolote_tipoembal":
				$query = self::fixed_values(array("C" => "Compra", "V" => "Venda"));
				break;
// N
			case "natoperacao":
				$query = "SELECT natoperacao, natoperacao || ' - ' || descricao FROM natoperacao".self::filter($filter)." ORDER BY natoperacao";
				$attributes = self::idtable($attributes, "NatOper", "Natureza de Opera&ccedil;&atilde;o");
				break;
			case "natoperacao_entsai":
				$query = self::fixed_values(array("E" => "Entrada", "S" => "Sa&iacute;da"));
				break;
			case "natoperacao_redbcsticmsprop":
				$query = self::fixed_values(array("N" => "Normal", "C" => "Redução no crédito", "A" => "Redução no crédito e débito"));
				break;
			case "natoperacao_tpnota":
				$query = self::fixed_values(array("CF" => "Cliente Final", "REP" => "Representante/Atacadista", "PRO" => "Produtor", "ME" => "Micro Empresa", "EPP" => "Empresa de Pequeno Porte", "FAB" => "Fabricante", "OUT" => "Outros"));
				break;
			case "natoperacao_tptribipi":
				$query = self::fixed_values(array("F" => "Fixo", "P" => "Percentual"));
				break;
			case "natureza_base_calculo_credito":
				$query = "SELECT codnaturezacredito, descricao FROM natbasecalculocreditopiscofins ".self::filter($filter)." ORDER BY codnaturezacredito";
				break;
			case "naturezatributacaoservico":
				$query = self::fixed_values(array("1" => "Simples Nacional", "2" => "Fixo", "3" => "Deposito em Juizo", "4" => "Exigibilidade suspensa por decisão judicial", "5" => "Exigibilidade suspensa por procedimento administrativo"));
				break;
			case "natreceita_tabela":
				$query = self::fixed_values(array("P" => "Produto", "D" => "Departamento", "G" => "Grupo", "S" => "SubGrupo"));
				break;
			case "ncmrel":
				$query = "SELECT codigoncm, codigoncm FROM ncm ".self::filter($filter)." ORDER BY codigoncm";
				$attributes = self::idtable($attributes, "NcmRel", "ncmrel");
				break;
			case "ncm":
				$query = "SELECT idncm, codigoncm || ' - ' || descricao FROM ncm ".self::filter($filter)." ORDER BY codigoncm";
				$attributes = self::idtable($attributes, "CadNcm", "NCM");
				break;
			case "notafiscaleletronicaestabelecimento_filtro":
				$query = self::fixed_values(array("996" => "Manifesto Realizado", "997" => "Aguardando Manifesto", "998" => "Aguardando Entrada", "999" => "Entrada Realizada"), FALSE, $filter);
				break;
			case "notafiscaleletronicaestabelecimento_manifesto":
				$query = self::fixed_values(array("210210" => "Ciência a Operação", "210200" => "Confirma a Operação", "210220" => "Desconhece a Operação", "210240" => "Operação Não Realizada"), FALSE, $filter);
				break;
			case "notafiscal_status":
				$query = self::fixed_values(array("A" => "Autorizado", "C" => "Cancelado", "P" => "Pendente", "D" => "Denegada", "I" => "Inutilizada", "O" => "Conferido", "R" => "Rejeitado"), FALSE, $filter);
				break;
			case "notafiscal_statusxml":
				$query = self::fixed_values(array("A" => "Autorizado", "C" => "Cancelado"), FALSE, $filter);
				break;
			case "notafrete_tipodocumentofiscal":
				$query = self::fixed_values(array("08" => "08 - Conhecimento de Transporte Rodoviário de Cargas", "09" => "09 - Conhecimento de Transporte Aquaviário de Cargas", "10" => "10 - Conhecimento de Transporte Aéreo de Cargas", "11" => "11 - Conhecimento de Transporte Ferroviário de Cargas", "57" => "57 - Conhecimento de Transporte Eletrônico", "67" => "67 - Conhecimento de Transporte Eletrônico para Outros Serviços"));
				break;
			case "nutricional":
				$query = "SELECT codnutricional, descricao FROM nutricional".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Nutricional", "Nutricional");
				break;
			case "nutricional_unidporcao":
				$query = self::fixed_values(array("0" => "gr", "1" => "ml", "2" => "un"));
				break;
			case "nutricional_decmedcas":
				$query = self::fixed_values(array("0" => "0", "1" => "1/4", "2" => "1/3", "3" => "1/2", "4" => "2/3", "5" => "3/4"));
				break;
			case "nutricional_medcaseira":
				$query = self::fixed_values(array("00" => "Colher(es) de Sopa", "01" => "Colher(es) de Caf�", "02" => "Colher(es) de Ch�", "03" => "X�cara(s)", "04" => "De X�cara(s)", "05" => "Unidade(s)", "06" => "Pacote(s)", "07" => "Fatia(s)", "08" => "Fatia(s) Fina(s)", "09" => "Peda�o(s)", "10" => "Folha(s)", "11" => "P�o(es)", "12" => "Biscoito(s)", "13" => "Bisnaguinha(s)", "14" => "Disco(s)", "15" => "Copo(s)", "16" => "Por��o(�es)", "17" => "Tablete(s)", "18" => "Sach�(s)", "19" => "Alm�ndega(s)", "20" => "Bife(s)", "21" => "Fil�(s)", "22" => "Concha(s)", "23" => "Bala(s)", "24" => "Prato(s) Fundo(s)", "25" => "Pitada(s)", "26" => "Lata(s)"), TRUE);
				break;
// O
			case "ocorrencia_status":
				$query = self::fixed_values(array("C" => "Conclu&iacute;do", "P" => "Pendente"));
				break;
			case "oferta":
				$query = "SELECT codoferta, descricao FROM oferta".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "ProgramacaoOferta", "Oferta de Produtos");
				break;
			case "oferta_status":
				$query = self::fixed_values(array("I" => "Inativa", "A" => "Ativa", "E" => "Encerrada"));
				break;
			case "operacaoemlote":
				$query = self::fixed_values(array("liquidacao" => "Liquida&ccedil;&atilde;o", "duplicata" => "Aceite de Duplicata", "reconciliar" => "Reconciliar", "cheque" => "Imprimir Cheque", "previa" => "Prévia de Lançamento" ));
				break;
			case "operacaonota":
				$query = "SELECT operacao, descricao FROM operacaonota ".self::filter($filter)." ORDER BY descricao";
				break;
			case "operacaonota":
				$query = "SELECT operacao, descricao FROM operacaonota ".self::filter($filter)." ORDER BY descricao";
				break;
			case "operacaonota_tipo":
				$query = self::fixed_values(array("E" => "Entrada", "S" => "Sa&iacute;da"));
				break;
			case "orcamento_status":
				$query = self::fixed_values(array("P" => "Pendente", "C" => "Cancelado", "A" => "Atendido", "L" => "Em An&aacute;lise", "E" => "Feedback E-mail"));
				break;
			case "orcamento_tipo":
				$query = self::fixed_values(array("I" => "Individual", "T" => "Totalizado"));
				break;
			case "ordemprodhome":
				$query = self::fixed_values(array("descricao" => "Descrição", "maisvendidos" => "Mais vendidos", "ultimoscadastros" => "Últimos cadastrados"));
				break;
			case "ordemservico_dificuldade":
				$query = self::fixed_values(array("1" => "F&aacute;cil", "2" => "M&eacute;dia", "3" => "Dif&iacute;cil", "4" => "Muito Dif&iacute;cil"));
				break;
			case "ordemservico_prioridade":
				$query = self::fixed_values(array("B" => "Baixa", "M" => "M&eacute;dia", "A" => "Alta", "U" => "Urgente"));
				break;
			case "ordemservico_status":
				$query = self::fixed_values(array("P" => "Pendente para An&aacute;lise", "L" => "Em An&aacute;lise", "O" => "Aguardando Aprova&ccedil;&atilde;o Or&ccedil;amento", "D" => "Em Desenvolvimento", "T" => "Em Testes", "B" => "Pendente para Publica&ccedil;&atilde;o", "C" => "Conclu&iacute;do", "N" => "Cancelado"));
				break;
			case "ordemservico_tipo":
				$query = self::fixed_values(array("C" => "Corre&ccedil;&atilde;o", "M" => "Melhoria", "I" => "Inclus&atilde;o", "O" => "Or&ccedil;amento", "G" => "Gerador de Relat&oacute;rio"));
				break;
			case "ordemservicoteste_status":
				$query = self::fixed_values(array("P" => "Pendente", "C" => "Concluido", "S" => "Suspenso"), TRUE, $filter);
				break;
			case "origem":
				$query = "SELECT origem, origem || ' - ' || descricao FROM origem ".self::filter($filter)." ORDER BY origem";
				break;
			case "origemreg":
				$query = self::fixed_values(array("W" => "WebSac", "E" => "Ecommerce", "T" => "Telefone"), TRUE, $filter);
				break;
			case "outroscreditodebito_codajuste":
				$query = "SELECT codajuste, codajuste FROM  tabelaajusteicms ".self::filter($filter)." ORDER BY codajuste	";
				break;
			case "outroscreditodebito_operacao":
				$query = self::fixed_values(array("P" => "Própria", "S" => "Sustituição Tributaria"), TRUE, $filter);
				break;
			case "outroscreditodebito_tipo":
				$query = self::fixed_values(array("OC" => "Outros Credito", "ED" => "Estorno de Debito", "OD" => "Outros Debitos","EC" => "Estorno de Credito"), FALSE, $filter);
				break;
// P
			case "painelfinanceiro_agrupamentodata":
				$query = self::fixed_values(array("d" => "Diário", "s" => "Semanal", "m" => "Mensal", "a" => "Anual"));
				break;
			case "painelfinanceiro_opcoes":
				$query = self::fixed_values(array("dtemissao" => "Data de Emissão", "dtvencto" => "Data de Vencimento", "dtliquid" => "Data de Liquidação", "favorecido" => "Favorecido", "catlancto" => "Categoria", "subcatlancto" => "SubCategoria", "banco" => "Banco"));
				break;
			case "pais":
				$query = "SELECT codpais, nome FROM pais ".self::filter($filter)." ORDER BY nome";
				break;
			case "paramcomissao_tipocomissao":
				$query = self::fixed_values(array("F" => "Por Faturamento", "L" => "Por Liquida&ccedil;&atilde;o"));
				break;
			case "paramfat_impped":
				$query = self::fixed_values(array("G" => "G&aacute;fica", "M" => "Matricial"));
				break;
			case "paramfat_tplucro":
				$query = self::fixed_values(array("P" => "Presumido", "R" => "Real"));
				break;
			case "paramfiscal_ambientenfe":
				$query = self::fixed_values(array("1" => "Produ&ccedil;&atilde;o", "2" => "Homologa&ccedil;&atilde;o"));
				break;
			case "paramrec_formarateio":
				$query = self::fixed_values(array("V" => "Valor", "P" => "Peso", "Q" => "Quantidade"));
				break;
			case "paramrec_tpender":
				$query = self::fixed_values(array("F" => "Faturamento", "E" => "Entrega"));
				break;
			case "param_nfedescoucomp":
				$query = self::fixed_values(array("D" => "Descri&ccedil;&atilde;o", "C" => "Complemento", "2" => "Descri&ccedil;&atilde;o 2"));
				break;
			case "pedido_finalidade":
				$query = self::fixed_values(array("1" => "Normal", "2" => "Complementar", "3" => "Nf-e ajuste", "4" => "Devolu&ccedil;&atilde;o/Retorno"));
				break;
			case "pedido_modfrete":
				$query = self::fixed_values(array("0" => "Por conta do emitente", "1" => "Por conta do destinat&aacute;rio", "2" => "Por conta de terceiros", "9" => "Sem frete"));
				break;
			case "pedido_tipoemissao":
				$query = self::fixed_values(array("1" => "Normal", "2" => "Conting&ecirc;ncia FS", "3" => "Conting&ecirc;ncia SCAN", "5" => "Conting&ecirc;ncia FS-DA", "6"=>"Conting&ecirc;ncia SVC-AN"));
				break;
			case "pedido_tipoqtdenfe":
				$query = self::fixed_values(array("E" => "Embalagem", "U" => "Unidade"));
				break;
			case "pedido_tiporateio":
				$query = self::fixed_values(array("F" => "Fixo", "P" => "Percentual"));
				break;
			case "pedido_status":
				$query = self::fixed_values(array("P" => "Pendente", "C" => "Cancelado", "A" => "Atendido", "L" => "Em An&aacute;lise"));
				break;
			case "piscofins":
				$query = "SELECT codpiscofins, descricao FROM piscofins".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadPisCofins", "PIS/Cofins");
				break;
			case "piscofins_tipo":
				$query = self::fixed_values(array("T" => "Tributado", "I" => "Isento", "M" => "Monofasico", "Z" => "Zero", "F" => "Substituído"));
				break;
			case "posicao_logotipo":
				$query = self::fixed_values(array("L" => "Esquerda", "C" => "Centralizado", "R" => "Direita"));
				break;
			case "planocontas":
				$query = "SELECT codconta, numconta || ' - ' || nome FROM planocontas".self::filter($filter)." ORDER BY numconta, nome";
				$attributes = self::idtable($attributes, "PlanoContas", "Plano de Contas");
				break;
			case "planocontas_tpconta":
				$query = self::fixed_values(array("A" => "Ambos", "C" => "Credora", "D" => "Devedora"));
				break;
			case "pontovenda_tipovenda":
				$query = self::fixed_values(array("3" => "Gera&ccedil;&atilde;o de Pedido", "1" => "Emiss&atilde;o de Nota Fiscal", "2" => "Pr&eacute; Venda"));
				break;
			case "prosoft_data":
				$query = self::fixed_values(array("R" => "Reconcilia&ccedil;&atilde;o", "L" => "Liquida&ccedil;&atilde;o","M" => "Movimento"));
				break;
			case "produto":
				$query = "SELECT codproduto, descricao FROM produto".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Produto", "Produto");
				break;
			case "produto_pesounid":
				$query = self::fixed_values(array("P" => "Peso Vari&aacute;vel", "U" => "Unidade"));
				break;
			case "produto_tipocompra":
				$query = self::fixed_values(array("0" => "Normal", "1" => "Apenas por cota&ccedil;&atilde;o", "2" => "Sem cota&ccedil;&atilde;o"));
				break;
			case "produto_tipopreco":
				$query = self::fixed_values(array("PA" => "Pre&ccedil;o Atacado", "PV" => "Pre&ccedil;o Varejo", "PAO" => "Oferta Atacado", "PVO" => "Oferta Varejo"));
				break;
// Q
			case "qtdprodhome":
				$query = self::fixed_values(array("4" => "4", "8" => "8", "12" => "12", "16" => "16"));
				break;
			case "quebra":
				$query = self::fixed_values(array("estabelecimento" => "Estabelecimento", "departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "Subgrupo"));
				break;
// R
			case "receita":
				$query = "SELECT codreceita, descricao FROM receita".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Receita", "Receita");
				break;
			case "relabcvendas_quebra":
				$query = self::fixed_values(array("departamento" => "Departamento", "estabelecimento" => "Estabelecimento", "fornecedor" => "Fornecedor", "grupoprod" => "Grupo", "produto" => "Produto", "subgrupo" => "SubGrupo", "marca" => "Marca", "familia" => "Familia" ));
				break;
			case "relpedido_quebra":
				$query = self::fixed_values(array("departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "SubGrupo"));
				break;
			case "reldatavalidade_quebra":
				$query = self::fixed_values(array("departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "SubGrupo"));
				break;
			case "relprecoproduto_quebra":
				$query = self::fixed_values(array("departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "SubGrupo"));
				break;
			case "relabcvendas_ordem":
				$query = self::fixed_values(array("0" => "Total da Venda", "1" => "Quantidade"));
				break;
			case "relabcvendas_tipo":
				$query = self::fixed_values(array("data" => "Data", "departamento" => "Departamento", "estabelecimento" => "Estabelecimento", "fornecedor" => "Fornecedor", "grupoprod" => "Grupo", "produto" => "Produto", "subgrupo" => "SubGrupo",  "marca" => "Marca", "familia" => "Familia"));
				break;
			case "relabcvendas_tipovenda":
				$query = self::fixed_values(array("C" => "Cupom (Frente de Caixa)", "N" => "Nota Fiscal (Retaguarda)"));
				break;
			case "relabcvendas_tipomargem":
				$query = self::fixed_values(array(">=" => "Maior >=", "<=" => "Menor <=", "=" => "Igual ="));
				break;
			case "relapurnotafiscal_quebra":
				$query = self::fixed_values(array("O" => "Operacao Nota","P" => "Parceiro"));
				break;
			case "relcomparavenda_comparapor":
				$query = self::fixed_values(array("departamento" => "Departamento", "estabelecimento" => "Estabelecimento", "grupoprod" => "Grupo", "produto" => "Produto", "subgrupo" => "SubGrupo"));
				break;
			case "relcomparavenda_ordenacao":
				$query = self::fixed_values(array("venda_1" => "Venda p1", "lucro_1" => "Lucro p1", "quantidade_1" => "Qtde. p1", "venda_2" => "Venda p2", "lucro_2" => "Lucro p2", "quantidade_2" => "Qtde. p2", "venda_dif" => "Venda Dif", "lucro_dif" => "Lucro Dif", "quantidade_dif" => "Qtde. Dif"));
				break;
			case "relatorio_orientacao":
				$query = self::fixed_values(array("P" => "Retrato", "L" => "Paisagem"));
				break;
			case "relatoriocoluna_alinhamento":
				$query = self::fixed_values(array("L" => "Esquerda", "R" => "Direita", "C" => "Centro"));
				break;
			case "relatoriocoluna_tipo":
				$query = self::fixed_values(array("S" => "Texto", "I" => "Inteiro", "N" => "Decimal", "D" => "Data", "H" => "Hora"), TRUE);
				break;
			case "relcomissao_ordem":
				$query = self::fixed_values(array("0" => "Nome do Funcion&aacute;rio", "1" => "Total de Venda", "2" => "Total de Comiss&atilde;o", "3" => "Quantidade Vendida"));
				break;
			case "relcomissao_status":
				$query = self::fixed_values(array("A" => "Em Aberto", "F" => "Fechado"));
				break;
			case "relcomissao_tiporelatorio":
				$query = self::fixed_values(array("A" => "Anal&iacute;tico", "S" => "Sint&eacute;tico", "D" => "Sint&eacute;tico por Dia"));
				break;
			case "relcompraxvenda":
				$query = self::fixed_values(array("departamento" => "Departamento", "estabelecimento" => "Estabelecimento", "grupoprod" => "Grupo", "produto" => "Produto", "subgrupo" => "SubGrupo"));
				break;
			case "relcompra_agrupamento":
				$query = self::fixed_values(array("departamento" => "Departamento", "estabelecimento" => "Estabelecimento", "fornecedor" => "Fornecedor", "produto" => "Produto"));
				break;
			case "relcompradiariadepto_tipocompra":
				$query = self::fixed_values(array("N" => "Normal", "B" => "Bonificado"));
				break;
			case "relcompra_quebra":
				$query = self::fixed_values(array("estabelecimento" => "Estabelecimento", "departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "Subgrupo", "dtemissao" => "Data de Emiss&atilde;o"));
				break;
			case "relcompraxvenda_quebra":
				$query = self::fixed_values(array("estabelecimento" => "Estabelecimento", "departamento" => "Departamento", "grupoprod" => "Grupo", "subgrupo" => "SubGrupo", "produto" => "Produto", "fornecedor" => "Fornecedor"));
				break;
			case "reldespesa":
				$query = self::fixed_values(array("C" => "Categoria", "T" => "Tipo", "CT" => "Ambos"));
				break;
			case "relestoquexvenda_tipodata":
				$query = self::fixed_values(array("1" => "Di&aacute;rio", "7" => "Semanal", "15" => "Quinzenal", "30" => "Mensal", "60" => "Bimestral", "183" => "Semestral", "365" => "Anual"));
				break;
			case "relextratobanco_datapesquisa":
				$query = self::fixed_values(array("reconciliacao" => "Data de Reconcilia&ccedil;&atilde;o", "liquidacao" => "Data de Liquida&ccedil;&atilde;o", "nao_conciliado" => "Data de Liquida&ccedil;&atilde;o(N&atilde;o Conciliado)"));
				break;
			case "relextratobanco_tiporel":
				$query = self::fixed_values(array("1" => "Padr&atilde;o", "2" => "Lado a lado"));
				break;
			case "rellancamento_aceiteduplicata":
				$query = self::fixed_values(array("S" => "Apenas lan&ccedil;amentos com aceite de duplicata", "N" => "Apenas lan&ccedil;amentos sem aceite de duplicata"));
				break;
			case "rellancamento_tiporelatorio":
				$query = self::fixed_values(array("0" => "Anal&iacute;tico", "1" => "Sint&eacute;tico"));
				break;
			case "rellancamento_quebra":
				$query = self::fixed_values(array("estabelecimento" => "Estabelecimento", "favorecido" => "Favorecido", "catlancto" => "Categoria", "subcatlancto" => "SubCategoria" ,"catsubcat" =>"Categoria/SubCategoria", "especie" => "Forma de Pagamento", "dtemissao" => "Data de Emiss&atilde;o", "dtvencto" => "Data de Vencimento", "dtliquid" => "Data de Liquida&ccedil;&atilde;o"));
				break;
			case "relanfsaida_quebra":
				$query = self::fixed_values(array("estabelecimento" => "Estabelecimento", "operacao" => "Operacao"));
				break;
			case "rellancamento_ordenacao":
				$query = self::fixed_values(array("valorliquido" => "Valor Liquido", "favorecido" => "Favorecido", "especie" => "Forma de Pagamento", "dtlancto" => "Data de Lan&ccedil;amento", "dtemissao" => "Data de Emiss&atilde;o", "dtvencto" => "Data de Vencimento", "dtliquid" => "Data de Liquida&ccedil;&atilde;o"));
				break;
			case "relmaparesumo_tiporelatorio":
				$query = self::fixed_values(array("A" => "Anal&iacute;tico", "S" => "Sint&eacute;tico"));
				break;
			case "relmetaporfuncionario_quebra":
				$query = self::fixed_values(array("anomes" => "M&ecirc;s", "nome" => "Nome do Funcion&aacute;rio"));
				break;
			case "relnfentrada_ordenacao":
				$query = self::fixed_values(array("dtemissao" => "Data Emissão", "dtentrega" => "Data de Entrega", "notafiscal" => "Nota fiscal", "natoperacao" => "Natureza da Operação"));
				break;
			case "relnfsaida_ordenacao":
				$query = self::fixed_values(array("dtemissao" => "Data Emissão", "dtentrega" => "Data de Entrega", "numnotafis" => "Nota fiscal", "natoperacao" => "Natureza da Operação"));
				break;
			case "relpiscofins_tiporelatorio":
				$query = self::fixed_values(array("0" => "Anal&iacute;tico", "1" => "Sint&eacute;tico", "2" => "SPED PIS/Cofins"));
				break;
			case "relprecocusto_precocusto":
				$query = self::fixed_values(array("1" => "Filtrar apenas custo", "2" => "Filtrar apenas pre&ccedil;o varejo", "3" => "Filtrar apenas pre&ccedil;o atacado"));
				break;
			case "relprecoproduto_tipopreco":
				$query = self::fixed_values(array("PA" => "Pre&ccedil;o Atacado", "PV" => "Pre&ccedil;o Varejo"));
				break;
			case "relprecoproduto_preco":
				$query = self::fixed_values(array(">" => "Maior que zero", "<" => "Menor que zero",  "=" => "Igual a zero" ));
				break;
			case "relproduto_custopreco":
				$query = self::fixed_values(array("0" => "Custo maior que pre&ccedil;o atacado", "1" => "Custo maior que pre&ccedil;o varejo", "2" => "Custo maior que ambos os pre&ccedil;os", "3" => "Custo zerado", "4" => "Pre&ccedil;o atacado zerado", "5" => "Pre&ccedil;o varejo zerado' UNION ALL SELECT '6', 'Ambos os pre&ccedil;os zerados"));
				break;
			case "relproduto_estoque":
				$query = self::fixed_values(array("0" => "Com estoque", "1" => "Negativo", "2" => "Zerado", "3" => "M&aacute;ximo", "4" => "M&iacute;nimo"));
				break;
			case "relproduto_quebra":
				$query = self::fixed_values(array("codestabelec" => "Estabelecimento", "coddepto" => "Departamento", "codgrupo" => "Grupo", "codsubgrupo" => "SubGrupo", "codfornec" => "Fornecedor"));
				break;
			case "relproduto_tipocusto":
				$query = self::fixed_values(array("customedsemimp" => "Custo M&eacute;dio Fiscal", "customedrep" => "Custo M&eacute;dio Reposi&ccedil;&atilde;o", "custorep" => "Custo Reposi&ccedil;&atilde;o", "custosemimp" => "Custo Fiscal", "custotab" => "Custo Tabela"));
				break;
			case "relproduto_tipodescr":
				$query = self::fixed_values(array("C" => "Completa", "R" => "Reduzida"));
				break;
			case "relsugcompra_ordenacao":
				$query = self::fixed_values(array("codproduto" => "Codigo do Produto", "descricaofiscal" => "Descri&ccedil;&atilde;o"));
				break;
			case "relsugcompra_tiposugestao":
				$query = self::fixed_values(array("1" => "Quantidade", "2" => "Embalagem","3" => "Bruta"));
				break;
			case "report_format":
				$query = self::fixed_values(array("html" => "HTML", "pdf1" => "PDF (com cabe&ccedil;alho)", "pdf2" => "PDF (sem cabe&ccedil;alho)", "excel" => "MS-Excel"), TRUE);
				break;
			case "representante":
				$query = "SELECT codrepresentante, nome FROM representante ".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "CadRepresentante", "Representantes");
				break;
			case "representante_situacao":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo", "F" => "F&eacute;rias", "L" => "Licen&ccedil;a"));
				break;
			case "restricoes_tipo":
				$query = self::fixed_values(array("G" => "Grupo de usu&aacute;rios", "U" => "Usu&aacute;rio"));
				break;
			case "rma_status":
				$query = self::fixed_values(array("P" => "Aguardando Aprova&ccedil;&atilde;o", "A" => "Aprovado", "R" => "Recebido", "L" => "Em An&aacute;lise", "F" => "Finalizado", "R" => "Reprovado", "C" => "Cancelado"));
				break;
			case "rma_tipo":
				$query = self::fixed_values(array("D" => "Devolu&ccedil;&atilde;o", "F" => "Troca (Defeito)", "E" => "Troca (Produto Errado)", "S" => "Troca (Desist&ecirc;ncia)"));
				break;
			case "relprecocusto_quebra":
				$query = self::fixed_values(array("codestabelec" => "Estabelecimento", "coddepto" => "Departamento", "codgrupo" => "Grupo", "codsubgrupo" => "SubGrupo"));
				break;

// S
			case "sazonal":
				$query = "SELECT codsazonal, descricao FROM sazonal ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Sazonalidade", "Sazonalidade");
				break;
			case "sazonal_tipodata":
				$query = self::fixed_values(array("ED" => "Entre Datas", "PM" => "Por M&ecirc;ses"));
				break;
			case "setorocorrencia":
				$query = "SELECT codsetor,descricao FROM setorocorrencia ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadSetorOcorrencia", "Setor de Ocorr&ecirc;ncia");
				break;
			case "simnao":
				$query = self::fixed_values(array("S" => "Sim", "N" => "N&atilde;o"));
				break;
			case "simprod":
				$query = "SELECT codsimilar, descricao FROM simprod ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Similar", "Similar");
				break;
			case "sintegra_finalidade":
				$query = self::fixed_values(array("1" => "Normal", "2" => "Retifica&ccedil;&atilde;o Total do Arquivo", "3" => "Retifica&ccedil;&atilde;o Aditiva de Arquivo", "5" => "Defazimento de Opera&ccedil;&otilde;es"));
				break;
			case "situacaolancto":
				$query = "SELECT codsituacao, descricao FROM situacaolancto ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadSituacaotLancto", "Situa&ccedil;&atilde;o de Lan&ccedil;amentos");
				break;
			case "statuscliente":
				$query = "SELECT codstatus, descricao FROM statuscliente ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadStatusCliente", "Status de Cliente");
				break;
			case "subcatlancto":
				$query = "SELECT codsubcatlancto, descricao FROM subcatlancto ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadSubCatLancto", "SubCategoria de Lan&ccedil;amentos")." ajaxFile=\"subcatlancto\" parentField=\"codcatlancto\" ";
				break;
			case "subgrupo":
				$query = "SELECT codsubgrupo, descricao FROM subgrupo ".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "SubGrupo", "SubGrupo de Produtos")." ajaxFile=\"subgrupo\" parentField=\"codgrupo\" ";
				break;

			case "natreceita_tabelacodigo":
				$query = "SELECT codnat, codnat || ' - ' || descnatureza FROM tabelanaturezareceita".self::filter($filter)." ORDER BY codnat LIMIT 0";
				$attributes = self::idtable($attributes, "NatReceita", "Natureza Receita")." ajaxFile=\"tabelanaturezareceita\" parentField=\"piscofins\" ";
				break;
// T
			case "tabelapreco":
				$query = "SELECT codtabela, descricao FROM tabelapreco".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "CadTabelaPreco", "Tabela de Pre&ccedil;o");
				break;
			case "tesouraria_status":
				$query = self::fixed_values(array("0" => "Pronto para gerar financeiro", "1" => "Financeiro gerado"));
				break;
			case "tesouraria_tipogeracao":
				$query = self::fixed_values(array("A" => "Anal&iacute;tico", "S" => "Sint&eacute;tico"));
				break;
			case "tipoarquivo":
				$query = self::fixed_values(array("X" => "XML", "D" => "PDF", "A" => "Ambos"));
				break;
			case "tipocadastro":
				//"P" => "Produto de Revenda","M" => "Produto de Matéria Prima","U" => "Produto de Uso e Consumo", "S" =>
				$query = self::fixed_values(array(
					"00" => "Mercadoria para Revenda",
					"01" => "Matéria-prima",
					"02" => "Embalagem",
					"03" => "Produto em Processo",
					"04" => "Produto Acabado",
					"05" => "Subproduto",
					"06" => "Produto Intermediário",
					"07" => "Material de Uso e Consumo",
					"08" => "Ativo Imobilizado",
					"09" => "Serviços",
					"10" => "Outros insumos",
					"99" => "Outros"));
				break;
			case "tipoaceite":
				$query = "SELECT codtipoaceite, descricao FROM tipoaceite".self::filter($filter)." ORDER BY codtipoaceite";
				break;
			case "tipocadastrocobranca":
				$query = self::fixed_values(array("1" => "Cobrança Registrada", "2" => "Cobrança Sem Registro", "3" => "Recusa do Débito Automático"));
				break;
			case "tipoajuste":
				$query = self::fixed_values(array("0" => "Normal", "1" => "IPI"));
				break;
			case "tipodesoneracao":
				$query = self::fixed_values(array("C" => "Condicional", "I" => "Incondicional"));
				break;
			case "tipodistribuicao":
				$query = self::fixed_values(array("1" => "Banco Distribui", "2" => "Cliente Distribui", "3" => "Banco Envia E-mail", "4" => "Banco Envia SMS"));
				break;
			case "tiposliquidacao":
				$query = self::fixed_values(array("M" => "Liquidação Manual", "B" => "Liquidação Retorno Bancário", "L" => "Liquidação Lote", "A" => "Liquidação Automatica"));
				break;
			case "tpcartao":
				$query = self::fixed_values(array("C" => "Cr&eacute;dito", "D" => "D&eacute;bito", "V" => "Voucher"));
				break;
			case "tipodocumento":
				$query = "SELECT codtpdocto, descricao FROM tipodocumento".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "TpDocto", "Tipo de Documento");
				break;
			case "tipodocumentofiscal":
				$query = self::fixed_values(array(
					"01" => "01 - Nota Fiscal",
					"1B" => "1B - Nota Fiscal Avulsa",
					"02" => "02 - Nota Fiscal de Venda a Consumidor",
					"2D" => "2D - Cupom Fiscal",
					"04" => "04 - Nota Fiscal de Produtor",
					"06" => "06 - Nota Fiscal/Conta de Energia Elétrica",
					"21" => "21 - Nota Fiscal de Serviço de Comunicação",
					"22" => "22 - Nota Fiscal de Serviço de Telecomunicação",
					"23" => "23 - GNRE",
					"25" => "25 - Manifesto de Carga",
					"27" => "27 - Nota Fiscal De Transporte Ferroviário De Carga",
					"28" => "28 - Nota Fiscal/Conta de Fornecimento de Gás Canalizado",
					"29" => "28 - Nota Fiscal/Conta De Fornecimento D''água Canalizada",
					"55" => "55 - Nota Fiscal Eletrônica",
					"59" => "59 - Cupom Fiscal Eletrônico CF-e"
				));
				break;
			case "tipodocumento_tipo":
				$query = self::fixed_values(array("E" => "Entrada", "S" => "Sa&iacute;da"));
				break;
			case "tipoemissao":
				$query = self::fixed_values(array("P" => "Pr&oacute;pio", "T" => "Terceiro"));
				break;
			case "tiporecibo":
				$query = self::fixed_values(array("0" => "Normal", "1" => "Em uma linha", "2" => "48 Colunas"));
				break;
			case "tipo_escrituracao":
				$query = self::fixed_values(array("O" => "Original", "R" => "Retificadora"));
				break;
			case "tipohistorico":
				$query = self::fixed_values(array("A" => "Altera&ccedil;&atilde;o", "E" => "Exclus&atilde;o", "I" => "Inclus&atilde;o"));
				break;
			case "tipoimportacao":
				$query = self::fixed_values(array("1" => "Importa��o por conta pr�pria", "2" => "Importa��o por conta e ordem", "3" => "Importa��o por encomenda"));
				break;
			case "tipooperacao":
				$query = self::fixed_values(array("1" => "Opera&ccedil;&atilde;o presencial", "2" => "Opera&ccedil;&atilde;o n&atilde;o presencial, pela internet", "3" => "Opera&ccedil;&atilde;o n&atilde;o presencial, teleatendimento","9" => "Opera&ccedil;&atilde;o n&atilde;o presencial, outros"));
				break;
			case "tipopreco":
				$query = self::fixed_values(array("A" => "Atacado", "V" => "Varejo", "C" => "Custo Reposição"));
				break;
			case "tipoprecoof":
				$query = self::fixed_values(array("N" => "Normal", "O" => "Oferta"));
				break;
			case "tipopreco_sys":
				$query = self::fixed_values(array("1" => "Varejo", "2" => "Atacado"));
				break;
			case "tiporemessa":
				$query = self::fixed_values(array("CB" => "Cobran&ccedil;a", "FC" => "Financiamento"));
				break;
			case "tiposervidor":
				$query = self::fixed_values(array("L" => "Local", "N" => "Nuvem"));
				break;
			case "tipotributacao":
				$query = self::fixed_values(array("T" => "Tributado", "I" => "Isento", "F" => "Substitui&ccedil;&atilde;o", "R" => "Reduzido", "N" => "N&atilde;o Tributado"));
				break;
			case "tipotributacaoservico":
				$query = self::fixed_values(array("1" => "Isento de ISS", "2" => "Imune", "3" => "N&atilde;o Incidencia", "4" => "N&atilde;o Tribut&aacute;vel Dentro do Municipio", "5" => "Retida", "6" => "Tribut&aacute;vel Dentro Municipio", "7" => "Tribt&aacute;vel Fora do Municipio"));
				break;
			case "tipoparceiro":
				if(isset($filter) && $filter == "nota_fiscal"){
					$query = self::fixed_values(array("C" => "Cliente", "E" => "Estabelecimento", "F" => "Fornecedor"), TRUE);
					$arr = array("C" => "cliente", "E" => "estabelecimento", "F" => "fornecedor");
				}else{
					$query = self::fixed_values(array("A" => "Administradora", "C" => "Cliente", "E" => "Estabelecimento", "F" => "Fornecedor", "R" => "Representante", "T" => "Transportadora", "U" => "Funcion&aacute;rio"), TRUE);
					$arr = array("A" => "administradora", "C" => "cliente", "E" => "estabelecimento", "F" => "fornecedor", "R" => "representante", "T" => "transportadora", "U" => "funcionario");
				}
				$str = "";
				foreach($arr as $i => $v){
					$str .= "$(this).val() == '".$i."' ? '".$v."' : ";
				}
				$str .= "''";
				$attributes .= " onchange=\"if(".'$'."(this).attr('codparceiro')){".'$'."('#' + ".'$'."(this).attr('codparceiro')).attr('identify',".$str."); ".'$'."('#' + ".'$'."(this).attr('codparceiro')).description(); }\" ";
				break;
			case "tipoparceiro_nf":
				$query = self::fixed_values(array("C" => "Cliente", "E" => "Estabelecimento", "F" => "Fornecedor"));
				$arr = array("C" => "Cliente", "E" => "Estabelecimento", "F" => "Fornecedor");
				$str = "";
				foreach($arr as $i => $v){
					$str .= "$(this).val() == '".$i."' ? '".$v."' : ";
				}
				$str .= "''";
				$attributes .= " onchange=\"if(".'$'."(this).attr('codparceiro')){".'$'."('#' + ".'$'."(this).attr('codparceiro')).attr('identify',".$str."); ".'$'."('#' + ".'$'."(this).attr('codparceiro')).description(); }\" ";
				break;
			case "tipopessoa":
				$query = self::fixed_values(array("F" => "F&iacute;sica", "J" => "Jur&iacute;dica"));
				break;
			case "tipomensagem":
				$query = "SELECT codtipomensagem, descricao FROM tipomensagem".self::filter($filter)." ORDER BY descricao";
				$attributes = self::idtable($attributes, "Tipomensagem", "Tipomensagem");
				break;
			case "tiponome":
				$query = self::fixed_values(array("R" => "Raz&atilde;o Social", "F" => "Fantasia"));
				break;
			case "tipovalor":
				$query = self::fixed_values(array("L" => "Liquido", "P" => "Parcela"));
				break;
			case "transportadora":
				$query = "SELECT codtransp, nome FROM transportadora".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Transportadora", "Transportadora");
				break;
			case "tv_status":
				$query = self::fixed_values(array("A" => "Ativo", "I" => "Inativo"));
				break;
// U
			case "unidade":
				$query = "SELECT codunidade, descricao FROM unidade ".self::filter($filter)." ORDER BY unidade.descricao";
				$attributes = self::idtable($attributes, "Unidade", "Unidade");
				break;
			case "usuario":
				$query = "SELECT login, nome FROM usuario".self::filter($filter)." ORDER BY nome";
				$attributes = self::idtable($attributes, "Usuario", "Usu&aacute;rio");
				break;
// V
			case "valorperc":
				$query = self::fixed_values(array("V" => "Valor", "P" => "Percentual"));
				break;
			case "versaoapinfe":
				$query = self::fixed_values(array("4.0.0" => "4.0.0", "5.0.0" => "5.0.0"));
				break;
			case "viatransporte":
				$query = self::fixed_values(array("1" => "Marítima", "2" => "Fluvial", "3" => "Lacustre", "4" => "Aérea", "5" => "Postal", "6" => "Ferroviária", "7" => "Rodoviária", "8" => "Conduto / Rede Transmissão", "9" => "Meios Próprios", "10" => "Entrada / Saída ficta"));
				break;

			case "validacaofiscal_tipo":
				$query = self::fixed_values(array(
					"1" => "",
					"2" => "",
					"3" => "",
					"3" => "",
					"3" => "",
				));
				break;

// W
// X
// Y
// Z
		}

		$attributes .= " table=\"".$cbname."\" filter=\"".$filter."\"";
		$options = self::mount($cbname, $query, $value, $attroptioncreate); /* OS 5240 */
		return self::select($id, $options, $attributes, $optionnull);
	}

	static function filter($filter){
		if($filter != NULL){
			return " WHERE ".$filter." ";
		}else{
			return " ";
		}
	}

	/* OS 5240 */
	static function mount($name, $query, $value, $attroptioncreate){
		global $con;
		if(!is_object($con)){
			$con = new Connection;
		}
		$decode = (strpos($query, " FROM ") !== FALSE);
		$res = $con->query($query);
		$arr = $res->fetchAll(0);
		foreach($arr as $row){
			$desc = $row[1];
			if($decode && find_special($desc)){
				$desc = utf8_decode($desc);
			}
			/* OS 5240 */
			$options[] = self::option(trim($row[0]), $desc, $value, (is_null($attroptioncreate) ? NULL : $attroptioncreate), (is_null($attroptioncreate) ? NULL : $row));
			//$options[] = self::option(trim($row[0]), $desc, $value);
		}
		return $options;
	}

	/* OS 5240 */
	static function option($key, $desc, $value, $attroptioncreate, $attrvalue){
		$selected = ($key == $value ? "selected" : "");
		if(!is_null($attroptioncreate)){
			$attr = self::prepareatrroption($attroptioncreate, $attrvalue);
		}
		return "<option value=\"{$key}\" {$selected} title=\"{$desc}\" alt=\"{$desc}\" $attr>{$desc}</option>";
		//return "<option value=\"{$key}\" {$selected} title=\"{$desc}\" alt=\"{$desc}\">{$desc}</option>";
	}

	static function select($id, $options, $attributes, $optionnull){
		if(!$optionnull){
			$html = "<option value=\"\"></option>";
		}
		if(is_array($options)){
			foreach($options as $option){
				$html .= $option;
			}
		}
		if(!is_null($id)){
			$html = "<select id=\"{$id}\" name=\"{$id}\" {$attributes}>{$html}</select>";
		}else{
			$html = "<select {$attributes}>{$html}</select>";
		}
		return utf8_encode($html);
	}

	static function fixed_values($arr, $auto_order = FALSE, $filter = NULL){
		$arr_query = array();
		foreach($arr as $key => $value){
			$arr_query[] = "SELECT '".$key."' AS chave, '".$value."' AS descricao";
		}
		$query = implode(" UNION ALL ", $arr_query);
		if($auto_order){
			$query = "(".$query.") ORDER BY 2";
		}
		$query = "SELECT * FROM (".$query.") AS tabela ";
		if(strlen($filter) > 0){
			$query .= "WHERE ".$filter;
		}
		return $query;
	}

	static function idtable($attributes, $idtable, $description){
		return $attributes." idtable=\"".$idtable.",".$description."\" ";
	}

	static function preparaattroptioncreate($attroptioncreate){
		return ",".implode(",", $attroptioncreate);
	}

	static function prepareatrroption($attroptioncreate, $attrvalue){
		foreach($attroptioncreate as $attropt){
			$attr .= "{$attropt}=\"{$attrvalue[$attropt]}\" ";
		}
		return $attr;
	}
}
