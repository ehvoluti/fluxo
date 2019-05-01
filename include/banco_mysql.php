<?php
//header("Content-type: text/html; charset=ISO-8859-1");
/**
 * Nesta função, vamos gerenciar a conexão com um banco de dados, direto de nosso
 * arquivo de configuração com informações do banco.
 */
function conectar() {
	global $config;
	
	mysql_connect($config['host'], $config['usuario'], $config['senha']);
	mysql_select_db($config['banco']);
}

/**
 * Nesta função, simplificamos a maneira de inserir dados em uma tabela.
 *
 * @param string $tabela Nome da tabela a receber dados
 * @param array $dados Dados a serem inseridos na tabela, em forma de um array multi-dimensional
 */
function inserir($tabela, $dados) {
	/**
	 * Para cada chave e valor em nosso array, criamos dois novos arrays.
	 * Um com colunas, outro com valores.
	 *
	 * $valores é um array com os valores a serem inseridos, envolvidos em aspas simples: 'lorem ipsum'.
	 * Logo abaixo usamos implode para transformar esses valores em uma string separada
	 * por vírgulas: 'lorem ipsum', 'dolor sit amet', 'nepet quisquam'
	 *
	 * Depois, basta jogar essa string na nossa query.
	 */
	foreach($dados as $coluna => $valor) {
		$colunas[] = "$coluna"; // Envolvemos o valor em crases para evitar erros na query SQL
		$valores[] = "'$valor'";
	}
	
	/**
	 * Transformamos nosso array de colunas em uma string, separada por vírgulas
	 */
	$colunas = implode(", ", $colunas);
	
	/**
	 * Transformamos nosso array de substitutos em uma string, separada por vírgulas
	 */
	$valores = implode(", ", $valores);
	
	/**
	 * Montamos nossa query SQL
	 */
	$query = "INSERT INTO $tabela ($colunas) VALUES ($valores)";
        
	/**
	 * Preparamos e executamos nossa query
	 */
        return mysql_query($query);
        
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
		$set[] = "`$coluna` = '$valor'";
	}
	
	/**
	 * Transformamos nosso array de valores em uma string, separada por vírgulas
	 */
	$set = implode(", ", $set);
	
	/**
	 * Montamos nossa query SQL
	 */
	$query = "UPDATE `$tabela` SET $set WHERE $onde";
	
	/**
	 * Preparamos e executamos nossa query
	 */
	return mysql_query($query);
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
	$query = "DELETE FROM `$tabela`";
	
	/**
	 * Caso tenhamos um valor de onde deletar dados, adicionamos a cláusula WHERE
	 */
	if(!empty($onde)) {
		$query .= " WHERE $onde";
	}
	
	/**
	 * Preparamos e executamos nossa query
	 */
	return mysql_query($query);
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
	$query = "SELECT $campos FROM `$tabela`";
	
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
	$consulta = mysql_query($query);
        
	/**
	 * Se tivermos resultados para nossa consulta
	 */
	if(mysql_num_rows($consulta) != 0) {
		/**
		 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
		 */
		while($item = mysql_fetch_assoc($consulta)) {
			$resultados[] = $item;
		}
		
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
	$query = "SELECT $campos FROM `$tabela`";
	
	/**
	 * Selecionamos onde queremos pegar este dado
	 */
	if(!empty($onde)) {
		$query .= " WHERE $onde";
	}
	
	/**
	 * Limitamos para apenas 1 resultado
	 */
	$query .= " LIMIT 1";

        /**
	 * Preparamos e executamos nossa query
	 */
	$consulta = mysql_query($query);
        
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = mysql_fetch_array($consulta);
	
	return $resultados;
}
