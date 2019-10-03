<?php

require_once("websac/require_file.php");
require_file("def/function.php");
require_file("class/connection.class.php");

class CheckList{

	static function draw($clname, $id, $attributes = NULL, $filter = NULL, $pesquisado = NULL){
		global $con;
		if(!is_object($con)){
			$con = new Connection;
		}

		switch($clname){
			case "departamento":
				$query = "SELECT coddepto, nome FROM departamento ORDER BY nome";
				break;
			case "catlancto":
				$query = "SELECT codcatlancto, descricao FROM catlancto ORDER BY descricao ";
				break;
			case "subcatlancto":
				$query = "SELECT codsubcatlancto, descricao FROM subcatlancto ".self::filter($filter)." ORDER BY descricao ";
				break;
			case "estabelecimento":
				if(param("RESTRICAO", "USUAESTABEL", $con) == "1"){
					$filter = (strlen($filter) > 0 ? $filter." AND " : " ")."codestabelec IN (SELECT codestabelec FROM usuaestabel WHERE login = '".$_SESSION["WUser"]."')";
				}
				$query = "SELECT codestabelec, nome FROM estabelecimento".(strlen($filter) > 0 ? " WHERE ".$filter : "")." ORDER BY nome";
				break;
			case "validacaofiscal":
				$query = array(
					array("001", "001 - Unidades com descrição igual a sigla"),
					array("101", "101 - Itens de nota fiscal que são bonificados porém não estão com CFOP de bonificação"),
					array("102", "102 - Itens de nota fiscal que não são bonificados porém estão com CFOP de bonificação"),
					array("103", "103 - Itens de nota fiscal que são bonificados porém foram tributados de PIS/Cofins"),
					array("104", "104 - Itens de nota fiscal de entrada isentos de PIS/Cofins no cadastro porém está tributado na nota fiscal"),
					array("105", "105 - Itens de nota fiscal de entrada tributados de PIS/Cofins no cadastro porém está isento na nota fiscal"),
					array("106", "106 - Itens de nota fiscal de saída isentos de PIS/Cofins no cadastro porém está tributado na nota fiscal"),
					array("107", "107 - Itens de nota fiscal de saída tributados de PIS/Cofins no cadastro porém está isento na nota fiscal"),
					array("108", "108 - Itens de nota fiscal de entrada tributados de PIS/Cofins, porém com natureza de operação que não permite crédito"),
					array("201", "201 - Diferença nas tributações entre os cupons e mapas resumo"),
					array("202", "202 - Itens de cupom que são tributados de PIS/Cofins no cadastro porém estão isentos no cupom"),
					array("203", "203 - Itens de cupom que são isentos de PIS/Cofins no cadastro porém estão tributados no cupom"),
					array("250", "250 - Notas fiscais com a chave de nota fiscal eletrônica inválidas"),
					array("301", "301 - Mapas resumo com falta de informações básicas")
				);
		}
		return self::mount($clname, $id, $attributes, $query, $pesquisado);
	}

	static function mount($name, $id, $attributes, $query, $pesquisado = NULL){
		global $con;
		if(!is_object($con)){
			$con = new Connection;
		}
		if(!is_array($query)){
			$res = $con->query($query);
			$arr = $res->fetchAll(0);
		}else{
			$arr = $query;
		}

		$html = "";
		if($pesquisado == NULL){
			$html .= "<div id=\"".$id."\" class=\"listbox ".$name."\" ".$attributes.">";
		}
		$html .= "<table>";
		$html .= "<tr><td>";
		$html .= "<input type=\"checkbox\" checked id=\"".$id."_checkall\" onclick=\"$('#".$id." :checkbox[key]').checked($(this).checked()).trigger('change')\"><label for=\"".$id."_checkall\" style=\"padding-left:5px\"><b>Marcar Todos</b></label>";
		$html .= "</td></tr>";
		foreach($arr as $row){
			$id_check = $id."_check_".$row[0];
			$html .= "<tr><td>";
			if(strpos($attributes, "childId=") !== FALSE){
				$html .= "<input type=\"checkbox\" checked key=\"".$row[0]."\" id=\"".$id_check."\" class=\"".$id."\"><label for=\"".$id_check."\" style=\"padding-left:5px\">".$row[1]."</label>";
			}else{
				$html .= "<input type=\"checkbox\" checked key=\"".$row[0]."\" id=\"".$id_check."\" class=\"".$id."\"><label for=\"".$id_check."\" style=\"padding-left:5px\">".$row[1]."</label>";
			}
			$html .= "</td></tr>";
		}
		$html .= "</table>";
		if($pesquisado == NULL){
			$html .= "</div>";
		}

		return $html;
	}

	function filter($filter){
		if($filter != NULL){
			return " WHERE ".$filter." ";
		}else{
			return " ";
		}
	}

}