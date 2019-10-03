<?php
class SearchField{
	static function draw($sfname,$id,$attributes=NULL,$value=NULL){
		switch($sfname){
			case "cliente": $attributes .= " idtable=\"ClientePF,Clientes\""; break;
			case "estabelecimento": $attributes .= " idtable=\"Estabel,Estabelecimentos\""; break;
			case "fornecedor": $attributes .= " idtable=\"Fornecedor,Fornecedores\" onchange=\"fornecedor_mix()\""; break;
			case "funcionario": $attributes .= " idtable=\"CadFuncionario,Funcion&aacute;rios\""; break;
			case "negociacaopreco": $attributes .= " idtable=\"NegociacaoPreco,NegociacaoPreco\""; break;
			case "pedido": $attributes .= " idtable=\"Pedido,Pedidos\""; break;
			case "produto": $attributes .= " idtable=\"Produto,Produtos\""; break;
			case "planocontas": $attributes .= " idtable=\"PlanoContas,PlanoContas\""; break;
			case "simprod": $attributes .= " idtable=\"Similar,Similares\""; break;
		}

		$attributes .= " identify=\"".$sfname."\" description=\"desc_".$id."\"";

		switch($sfname){
			case "fornecedor": $mask = "inteiro2"; break;
			case "produto":
			$eanletras = param("CADASTRO","EANLETRAS");
			echo script("var param_cadastro_eanletras = '{$eanletras}'" );
			$mask = ($eanletras === "S" ? "ean" : "");
			break;
			case "planocontas": $mask = ""; break;
			default: $mask = "inteiro"; break;
		}

		$html  = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"searchfield-table\" style=\"height:100%; margin:0px; width:100%\"><tr><td align=\"left\" style=\"width:60px\">";
		$html .= "<input type=\"text\" id=\"".$id."\" class=\"field_p desabilitar\" ".(in_array($sfname,array("notafiscal")) ? "readonly" : "")." style=\"width:100%\" value=\"".$value."\" mask=\"".$mask."\" ".$attributes.">";
		if($sfname == "notafiscal"){
			$onclick = "$('#idnotafiscal').removeAttr('disabled').locate({table:$('#idnotafiscal').attr('identify'),filter:$('#idnotafiscal').attr('filter')}).disabled(true);";
		}else{
			$onclick = "$('#".$id."').locate({table:$('#".$id."').attr('identify'),filter:$('#".$id."').attr('filter')})";
		}
		$html .= "</td><td align=\"center\" style=\"width:15px\">";
		$html .= "<img src=\"../img/search11.jpg\" style=\"cursor:default\" alt=\"Pesquisar\" title=\"Pesquisar\" onclick=\"".$onclick."\">";
		$html .= "</td><td align=\"left\">";
		if(!in_array($sfname,array("pedido"))){
			$html .= "<input type=\"text\" id=\"desc_".$id."\" tabindex=\"-1\" style=\"width:100%\" ".(!in_array($sfname,array("produto")) ? "readonly" : "placeholder=\"Pesquise aqui pela descri&ccedil;&atilde;o\"").">";
		}
		$html .= "</td></tr></table>";
		if(strlen($value) > 0){
			$html .= "<script type=\"text/javascript\"> $(\"#".$id."\").description(); </script>";
		}
		return $html;
	}
}
