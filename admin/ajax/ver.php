<?php
// Incluir aquivo de conexão
require("../../include/config.php");

// Recebe o valor enviado
$valor = $_GET['valor'];
$tabela = $_GET['tabela'];
$campos = $_GET['campos'];

//Ver dados de um registro apenas 
$busca = ver($tabela, $campos, $valor); 
switch ($tabela) {
	case "catlancto":
		echo $busca[codcatlancto]." : ".$busca[descricao];
		break;
	case "subcatlancto":
		echo $busca[codsubcatlancto]." : ".$busca[descricao]." :".$busca[codcatlancto];
		break;
	case "fornecedor":
		echo $busca[codfornec].":".$busca[nome].":".$busca[codbanco].":".$busca[codcatlancto].":".$busca[codsubcatlancto];
		break;		
}
?>