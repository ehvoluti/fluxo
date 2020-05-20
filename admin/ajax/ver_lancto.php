<?php
// Incluir aquivo de conexão
require("../../include/config.php");

// Recebe o valor enviado
$onde = "dtvencto>='2020-05-01' AND codcatlancto=".$_GET['onde'];
$tabela = $_GET['tabela'];
$campos = "codlancto, favorecido, referencia,valorpago";


//Ver dados de um registro apenas 
$busca = listar($tabela, $campos, $onde); 
//var_dump($busca)
//echo $busca[codlancto].":".$busca[favorecido].":".$busca[referencia].":".$busca[valorpago];
echo json_encode($busca);
?>