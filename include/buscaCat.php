<?php
/**
 * Nesta função, vamos gerenciar a conexão com um banco de dados, direto de nosso
 * arquivo de configuração com informações do banco.
 */
function conectar() {
	global $config;

	//pg_connect("host=localhost port=5432 dbname=blog11 user=blog5 password=123456") or die("Erro de conexao com o banco");
	pg_connect("host={$config['host']}  port={$config['port']}  dbname={$config['banco']} user={$config['usuario']} password={$config['senha']} ") or die ("Erro de conexao com o banco: Contate o administrador");
}



function combocat($onde) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT codsubcatlancto, descricao FROM subcatlancto WHERE codcatlancto = $onde;";
	
	$consulta = pg_query($query);
	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}
