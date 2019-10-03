<?php
require_once("../class/pdvconfig.class.php");
require_once("../class/pdvitem.class.php");
require_once("../class/pdvfinalizador.class.php");
require_once("../class/pdvvenda.class.php");

class VisualMix{
	private $con;
	private $pdvconfig;
	private $pdvvenda;
	private $pdvfinalizador;

	function __construct(){
		$this->limpar_dados();
	}

	function setpdvconfig($pdvconfig){
		$this->pdvconfig = $pdvconfig;
		$this->con = $this->pdvconfig->getconnection();
	}

	function exportar_produto(){
		setprogress(0,"Buscando produtos",TRUE);
		$arr_linha = array();

		// Cria o header do arquivo
		$header  = "H"; // Tipo de registro
		$header .= $this->valor_data(date("d/m/Y")); // Data do arquivo
		$header .= str_pad($this->pdvconfig->getestabelecimento()->getcodestabelec(),5," ",STR_PAD_LEFT); // Codigo do estabelecimento
		$header .= str_repeat(" ",209);
		$arr_linha[] = $header;

		$query  = "SELECT produto.codproduto, produtoean.codean, ".$this->pdvconfig->sql_descricao().", produto.codvasilhame, produto.diasvalidade, ";
		$query .= "	classfiscal.aliqicms, classfiscal.tptribicms, unidade.codunidade, unidade.sigla AS unidade, embalagem.quantidade, produto.pesado, ";
		$query .= "	produto.pesounid, produtoestab.precovrj, produtoestab.precovrjof, produtoestab.precoatc, produtoestab.precoatcof, produto.codsimilar, ";
		$query .= "	produto.margemvrj, classfiscal.codcst ";
		$query .= "FROM produtoestab ";
		$query .= "INNER JOIN produto ON (produtoestab.codproduto = produto.codproduto) ";
		$query .= "INNER JOIN produtoean ON (produto.codproduto = produtoean.codproduto) ";
		$query .= "INNER JOIN classfiscal ON (produto.codcfpdv = classfiscal.codcf) ";
		$query .= "INNER JOIN embalagem ON (produto.codembalvda = embalagem.codembal) ";
		$query .= "INNER JOIN unidade ON (embalagem.codunidade = unidade.codunidade) ";
		$query .= "WHERE produtoestab.codestabelec = ".$this->pdvconfig->getestabelecimento()->getcodestabelec()." ";
		$query .= "	AND produto.gerapdv = 'S' ";
		if(param("ESTOQUE","CARGAITEMCOMESTOQ",$this->con) == "S"){
			$query .= " AND produtoestab.sldatual > 0 ";
		}
		if(param("ESTOQUE","ENVIAFORALINHAPDV",$this->con) == "N"){
			$query .= "	AND produto.foralinha = 'N' ";
		}
		if($this->pdvconfig->produto_parcial()){
			$query .= "	AND produto.codproduto IN (SELECT DISTINCT codproduto FROM logpreco WHERE data > '".$this->pdvconfig->getdatalog()."' OR (data = '".$this->pdvconfig->getdatalog()."' AND hora >= '".$this->pdvconfig->gethoralog()."')) ";
		}
		if($this->pdvconfig->geturgente()){
			$query .= " AND produtoestab.urgente = 'S' ";
		}

		$res = $this->con->query($query);
		$arr = $res->fetchAll(2);

		foreach($arr as $i => $row){
			setprogress((($i + 1) / sizeof($arr) * 100),"Exportando produtos: ".($i + 1)." de ".sizeof($arr));
			$linha  = "D"; // Tipo de registro
			$linha .= "0"; // Tipo de operacao (0 = inclusao/alteracao; 1 = exclusao do codigo de automacao; 2 = exclusao de embalagem; 3 = grava produto como excluido)
			$linha .= "I"; // Tipo de acao (I = imediato; D = dia seguinte)
			$linha .= "N"; // Situacao (N = normal; D = descontinuado; E = excluido)
			$linha .= str_pad(strrev(substr(strrev($row["codean"]),1)),13," ",STR_PAD_LEFT); // Codigo automacao
			$linha .= str_pad(substr($row["codean"],-1),1," ",STR_PAD_LEFT); // Digito automacao
			$linha .= str_pad($row["codproduto"],10," ",STR_PAD_LEFT); // Codigo interno
			$linha .= " "; // Digito interno
			$linha .= str_pad(substr($row["descricaofiscal"],0,35),35," ",STR_PAD_RIGHT);
			$linha .= str_pad(substr($row["descricao"],0,15),15," ",STR_PAD_RIGHT);
			$linha .= str_pad($row["codvasilhame"],13," ",STR_PAD_LEFT);
			$linha .= " "; // Digito vasilhame
			$linha .= str_pad($this->valor_decimal(($this->pdvconfig->gettipopreco() == "A" ? $row["precoatc"] : $row["precovrj"]),2),11," ",STR_PAD_LEFT); // Preco de venda
			$linha .= str_pad($this->valor_decimal(0,2),11," ",STR_PAD_LEFT); // Preco de custo sem imposto
			$linha .= str_pad($row["diasvalidade"],4," ",STR_PAD_LEFT); // Dias de validade
			$linha .= $this->codigo_icms($row["tptribicms"],$row["aliqicms"]); // Codigo da aliquota PDV
			$linha .= " 5"; // Codigo da aliquota nota fiscal
			$linha .= str_pad($row["codunidade"],2," ",STR_PAD_LEFT); // Codigo da unidade
			$linha .= str_pad($row["sigla"],3," ",STR_PAD_RIGHT); // Descricao da unidade
			$linha .= str_pad($this->valor_decimal($row["quantidade"],3),7," ",STR_PAD_LEFT); // Quantidade na embalagem
			$linha .= $row["pesado"]; // Peso variavel
			$linha .= ($row["pesounid"] == "P" ? "S" : "N"); // Vende quantidade fracionaria
			$linha .= " 1"; // Codigo do tipo de etiqueta de gondola
			$linha .= "  1"; // Quantidade de etiqueta de gondola
			$linha .= " 1"; // Codigo do tipo de etiqueta de produto
			$linha .= str_repeat(" ",8); // Data de inicio da promocao
			$linha .= str_repeat(" ",8); // Data de fim da promocao
			$linha .= str_pad($this->valor_decimal(($this->pdvconfig->gettipopreco() == "A" ? $row["precoatcof"] : $row["precovrjof"]),2),11," ",STR_PAD_LEFT); // Preco de venda da promocao
			$linha .= "  1"; // Codigo do tipo da promocao
			$linha .= str_repeat(" ",5); // Mercadologico 1
			$linha .= str_repeat(" ",5); // Mercadologico 2
			$linha .= str_repeat(" ",5); // Mercadologico 3
			$linha .= str_repeat(" ",5); // Mercadologico 4
			$linha .= str_repeat(" ",5); // Mercadologico 5
			$linha .= str_repeat(" ",8); // Codigo do fornecedor
			$linha .= str_pad($row["codsimilar"],4," ",STR_PAD_LEFT); // Codigo do similar
			$linha .= str_pad($this->valor_decimal($row["margemvrj"],2),5," ",STR_PAD_LEFT); // Margem teorica
			$linha .= str_pad(substr($row["codcst"],0,3),3,"0",STR_PAD_LEFT); // Situacao tributaria
			$linha .= "1"; // Tipo de PIS/Cofins
			$linha .= "N"; // Bloqueia quantidade
			$linha .= "N"; // Bloqueia venda convenio
			$linha .= "N"; // Produto de fabricacao propria
			$arr_linha[] = $linha;
		}

		// Cria rodape do arquivo
		$footer  = "R"; // Tipo de registro
		$footer .= str_pad(sizeof($arr_linha) + 1,8," ",STR_PAD_LEFT); // Quantidade de registros
		$footer .= str_repeat(" ",214);
		$arr_linha[] = $footer;

		$this->pdvconfig->file_create("CADPROD.TXT",$arr_linha);
	}

	function codigo_icms($tptribicms,$aliqicms){
		switch($tptribicms){
			case "R":
			case "T":
				switch($aliqicms){
					case 18: return " 1";
					case 12: return " 2";
					case 25: return " 3";
					case  4: return " 4";
				}
			case "I": return " 5";
			case "F": return " 6";
			case "N": return " 7";
		}
	}

	private function limpar_dados(){
		$this->pdvvenda = array();
		$this->pdvfinalizador = array();
	}

	function valor_data($data){
		return convert_date(value_date($data),"Y-m-d","dmY");
	}

	function valor_decimal($valor,$dec){
		return number_format($valor,$dec,"","");
	}
}
?>