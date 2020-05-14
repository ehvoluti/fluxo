<?php
// Incluir aquivo de conexão
require("../../include/config.php");

// Recebe o valor enviado
$valor = $_GET['valor'];
$tabela = $_GET['tabela'];
$campos = $_GET['campos'];

//Ver dados de um registro apenas 
$busca = ver($tabela, $campos, $valor); 
echo $busca[categ];
?>