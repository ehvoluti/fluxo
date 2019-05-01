<?php

/**
 * Verificamos se está logado ou não
 *
 * @return int Retornaremos 2, 1 ou 0, de acordo com o status do usuário: 2 é administrador, 1 é usuário logado e 0 é não-logado
 */
function logar($login, $senha) {
    //$senha=md5($senha);
	//echo $login;
    $login=pg_escape_string($login);
    $resultado = ver("usuario", "*", "login = '$login' AND senha = '$senha'");

	
    //Terminar opção de login
    if ($resultado) {
        if ($resultado["login"] <> 'xxx') {
            $_SESSION['logado'] = true;
            $_SESSION['usuario'] = $resultado["nome"];
            return true;
        }
    }
    return false;
}

function logado() {
    if (isset($_SESSION['logado']) && $_SESSION['logado'] == true) {
        return true;
    } else {
        return 0;
    }
}

function logout() {
    $_SESSION = array();
    session_destroy();
}
