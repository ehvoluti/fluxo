<?php
// Incluir aquivo de conexão
require("../../include/config.php");

// Recebe o valor enviado
$valor = $_GET['valor'];

//Busca Saldo
$versaldo = saldo($valor); 
echo $versaldo[banco]." : ".$versaldo[saldo];

?>