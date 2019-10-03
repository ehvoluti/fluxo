<?php

session_start();

require_once("websac/require_file.php");
require_file("class/checklist.class.php");
require_file("class/combobox.class.php");
require_file("class/connection.class.php");
require_file("class/objectsession.class.php");
require_file("class/searchfield.class.php");
require_file("class/temporary.class.php");
require_file("class/temporarylocalfile.class.php");
require_file("class/grid.class.php");
require_file("class/chart.class.php");
require_file("class/log.class.php");
require_file("class/logerror.class.php");
require_file("def/function.php");

if(file_exists("../def/constant.php")){
	include("../def/constant.php");
}

mobile_redirect();

// Verifica login
$login = $_SESSION["WUser"];
if(strlen($login) === 0){
	switch(basename(dirname($_SERVER["PHP_SELF"]))){
		case "ajax":
			echo messagebox("alert", "Sessão expirada", "A sua sessão foi expirada.<br>Por favor, efetue o login novamente.", null, "window.open('../form/login.php?close')");
			break;
		default:
			header("Location: ../form/login.php");
			break;
	}
	die();
}

// Verifica a permissao de acesso a pagina
$arquivo = $_SERVER["PHP_SELF"];
$idtable = $_REQUEST["idtable"];
$cadastro = $_REQUEST["cadastro"];
$report = $_REQUEST["report"];

$encontrou = FALSE;
$autorizado = FALSE;

if(basename(dirname($arquivo)) == "form"){
	$con = new Connection();
	$res = $con->query("SELECT idtable, programa FROM programa WHERE programa LIKE '%".basename($arquivo)."%'");
	$arr = $res->fetchAll(2);
	if(is_array($arr) && sizeof($arr) > 0){
		foreach($arr as $row){
			if($idtable == $row["idtable"]){
				if(strlen($cadastro) > 0){
					if(strpos($row["programa"], "cadastro=".$cadastro) !== FALSE){
						$encontrou = TRUE;
					}
				}elseif(strlen($report) > 0){
					if(strpos($row["programa"], "report=".$report) !== FALSE){
						$encontrou = TRUE;
					}
				}else{
					$encontrou = TRUE;
				}
			}
			if($encontrou){
				break;
			}
		}

		if($encontrou){
			// Verifica se o usuario tem permissao para acessar a pagina
			$usuario = objectbytable("usuario", $login, $con);
			$grupoprograma = objectbytable("grupoprograma", array($usuario->getcodgrupo(), $idtable), $con);
			$usuarestricao = objectbytable("usuarestricao", array($usuario->getlogin(), $idtable), $con);
			if($grupoprograma->exists() && !$usuarestricao->exists()){
				$autorizado = TRUE;
			}
		}
	}else{
		$autorizado = TRUE;
	}
	if($cadastro == "notafiscalservico"){
		$autorizado = TRUE;
	}
	$variaveis_js = TRUE;
	$arquivo = basename($_SERVER["PHP_SELF"], ".php");
	$arr_termo = array("delproduto","divergencia", "extrato", "financiamento", "imprimir", "pedido_distribuicao", "planilha", "ranking", "recibo", "report", "romaneio", "saneamento_erro", "validacaofiscal_relatorio");
	foreach($arr_termo as $termo){
		if(strpos($arquivo, $termo) !== FALSE){
			$variaveis_js = FALSE;
			break;
		}
	}

	if((($autorizado && $variaveis_js) || in_array($arquivo, array("report", "recibo"))) && !in_array($arquivo, array("livrofiscal_entrada", "maladireta_gerarlista"))){
		echo script("window.idtable = '{$idtable}'");
		echo script("window.serverDate = '".date("d/m/Y")."'");
	}
}else{
	$autorizado = TRUE;
}

if(!$autorizado){
	echo "<script type=\"text/javascript\"> window.opener.$.messageBox({type:\"error\",title:\"Acesso negado!\",text:\"Usu&aacute;rio n&atilde;o tem permiss&atilde;o para acessar a p&aacute;gina.\"}); window.close(); </script>";
	die();
}
