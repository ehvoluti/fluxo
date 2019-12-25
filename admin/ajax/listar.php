<?php
// Incluir aquivo de conexão
require("../../include/config.php");

// Recebe o valor enviado
$valor = $_GET['valor'];
$tabela = $_GET['tabela'];
$campos = $_GET['campos'];

//Ver dados de um registro apenas 
$busca = combocat($tabela,$campos,$valor); 
switch ($tabela) {
	case "subcatlancto":
		foreach ($busca as $xbusca):
			echo $xbusca[codsubcatlancto].":".$xbusca[descricao].":".$xbusca[codcatlancto].",";
		endforeach;
		break;
}
?>