<?php
//require_once('MinhaException.php');
/**
 * Nesta função, vamos gerenciar a conexão com um banco de dados, direto de nosso
 * arquivo de configuração com informações do banco.
 */
 function conectar() {
	global $config;

	//pg_connect("host=localhost port=5432 dbname=blog11 user=blog5 password=123456") or die("Erro de conexao com o banco");
	//pg_connect("host={$config['host']}  port={$config['port']}  dbname={$config['banco']} user={$config['usuario']} password={$config['senha']} ") 	or die ("Erro de conexao com o banco: Contate o administrador");
	$pdocon = new PDO("pgsql:host={$config['host']}  port={$config['port']}  dbname={$config['banco']} user={$config['usuario']} password={$config['senha']} ") 	or die ("Erro de conexao com o banco: Contate o administrador");	
}

/**
 * Nesta função, simplificamos a maneira de inserir dados em uma tabela.
 *
 * @param string $tabela Nome da tabela a receber dados
 * @param array $dados Dados a serem inseridos na tabela, em forma de um array multi-dimensional
 */
 function inserir($tabela, $dados) {
	//echo $tabela.":".$dados;
	//$dados = str_replace($dados,',','');
	
	foreach($dados as $coluna => $valor) {
		$colunas[] = "$coluna"; // Envolvemos o valor em crases para evitar erros na query SQL
		$valor=strip_tags($valor);		
		$valor=pg_escape_string($valor);
		$valores[] = "'$valor'";
	}
	/*$colunas = array_keys($dados);
	$valores = array_values($dados);*/
	
	//Fiz esse tratamento para tirar virgula dos campos de valores maior que 999.99
	$valores = str_replace(',','',$valores);
	//var_dump ($valores);
	
	$colunas = implode(", ", $colunas);
		
	$valores = implode(", ", $valores);
	
	$query = "INSERT INTO $tabela ($colunas) VALUES ($valores)";
    //echo $query; 	
	
	try{
		return pg_query($query);
		throw new Exception (PDOException,1, null);
	}
	catch (PDOException $e) {
		die;
		echo "Algum erro no INSERT!!<br><br>".$e->getMessage();
	}	
}

/**
 * Nesta função, simplificamos a maneira de alterar dados em uma tabela.
 *
 * @param string $tabela Nome da tabela a ter dados alterados
 * @param string $onde Onde os dados serão alterados
 * @param array $dados Dados a serem alterados na tabela, em forma de um array multi-dimensional
 */
function alterar($tabela, $onde, $dados) {
	
	/**
	 * Pegaremos os valores e campos recebidos no método e os organizaremos
	 * de modo que fique mais fácil montar a query logo a seguir
	 */
	foreach($dados as $coluna => $valor) {
		$set[] = "$coluna = '$valor'";
	}
	
	/**
	 * Transformamos nosso array de valores em uma string, separada por vírgulas
	 */
	$set = implode(", ", $set);

	
	/**
	 * Montamos nossa query SQL
	 */
	$query = "UPDATE $tabela SET $set WHERE $onde";
	
	
	/**
	 * Preparamos e executamos nossa query
	 */
	return pg_query($query);
}

/**
 * Nesta função, simplificamos a maneira de remover dados de uma tabela.
 *
 * @param string $tabela Nome da tabela a ter dados removidos
 * @param string $onde Onde os dados serão removidos
 */ 
function remover($tabela, $onde = null) {

	/**
	 * Montamos nossa query SQL
	 */
	$query = "DELETE FROM $tabela";
	
	/**
	 * Caso tenhamos um valor de onde deletar dados, adicionamos a cláusula WHERE
	 */
	if(!empty($onde)) {
		$query .= " WHERE $onde";
	}
	
	/**
	 * Preparamos e executamos nossa query
	 */
	return pg_query($query);
}

/**
 * Nesta função, simplificamos a maneira de consultar dados de uma tabela.
 *
 * @param string $tabela Nome da tabela a ter dados consultados
 * @param string $campos Quais campos serão selecionados na tabela
 * @param string $onde Onde os dados serão consultados
 * @param string $ordem Ordem dos dados a serem consultados
 * @param string $filtro Filtrar dados consultados por conter uma palavra
 * @param string $limite Limitar dados consultados
 */
function listar($tabela, $campos, $onde = null, $filtro = null, $ordem = null, $limite = null) {
	
	/**
	 * Montamos nossa query SQL
	 */
	$query = "SELECT $campos FROM $tabela";
	
	/**
	 * Caso tenhamos um valor de onde selecionar dados, adicionamos a cláusula WHERE
	 */
	if(!empty($onde)) {
		$query .= " WHERE $onde";
	}
	
	/**
	 * Caso tenhamos um valor de como filtrar dados que contenham uma regra, adicionamos a cláusula LIKE
	 */
	if(!empty($filtro)) {
		$query .= " LIKE $filtro";
	}
	
	/**
	 * Caso tenhamos um valor de como ordenar dados, adicionamos a cláusula ORDER BY
	 */
	if(!empty($ordem)) {
		$query .= " ORDER BY $ordem";
	}
	
	/**
	 * Caso tenhamos um valor de como limitar os dados consultados, adicionamos a cláusula LIMIT
	 */
	if(!empty($limite)) {
		$query .= " LIMIT $limite";
	}
	
	/**
	 * Preparamos e executamos nossa query
	 */
	
	//	$consulta = pg_query($query);
	
	//Troquei a função usando a conexão com PDO
		//$query = "SELECT * FROM banco";
		//echo $query;	
		$con2 = $pdocon->query($query);
		$con2->execute();
		
		//$resultados = $con->fetchAll();
	

	
	/**
	 * Se tivermos resultados para nossa consulta
	 */
	if($consulta = $con2->rowCount() != 0) {
		/**
		 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
		 */
		//$resultados=pg_fetch_all($consulta) ;
		
		//Troquei a função usando a conexão com PDO
		//$resultados = $consulta->fetchAll();
						
		return $resultados;
	}
}

/**
 * Nesta função, simplificamos a maneira de consultar apenas um dado de uma tabela
 *
 * @param string $tabela Nome da tabela a ter dados consultados
 * @param string $campos Quais campos serão selecionados na tabela
 * @param string $onde Onde os dados serão consultados
 */
function ver($tabela, $campos, $onde) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT $campos FROM $tabela";
	
	
	$query .= " WHERE $onde";
	
	//echo $query;
	/**
	 * Limitamos para apenas 1 resultado
	 */
	$query .= " LIMIT 1";
	
	/**
	 * Preparamos e executamos nossa query
	 */
	
	//$consulta = pg_query($query);
	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	//$resultados = pg_fetch_assoc($consulta);
	
	
	//Troquei a função usando a conexão com PDO
		$con = $pdocon->prepare($query);
		$con-> execute();
		
		$resultados = $con->fetchAll();
	
	
	return $resultados;
}

function combocat($onde) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT codsubcatlancto, descricao FROM subcatlancto WHERE codcatlancto = $onde;";
	
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_all($consulta);
	
	return $resultados;
}
