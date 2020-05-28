<?php
/**
 * Nesta função, vamos gerenciar a conexão com um banco de dados, direto de nosso
 * arquivo de configuração com informações do banco.
 */
function conectar() {
	global $config;

	//pg_connect("host=localhost port=5432 dbname=blog11 user=blog5 password=123456") or die("Erro de conexao com o banco");
	pg_connect("host={$config['host']}  port={$config['port']}  dbname={$config['banco']} user={$config['usuario']} password={$config['senha']}") or die ("Erro de conexao com o banco: Contate o administrador");
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
	return pg_query($query);
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
	//echo $query;
	/**
	 * Preparamos e executamos nossa query
	 */
	$consulta = pg_query($query);
	
	/**
	 * Se tivermos resultados para nossa consulta
	 */
	if(pg_num_rows($consulta) != 0) {
		/**
		 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
		 */
		$resultados=pg_fetch_all($consulta) ;
						
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
	
	
	/**
	 * Limitamos para apenas 1 resultado
	 */
	$query .= " LIMIT 1";
	
	/**
	 * Preparamos e executamos nossa query
	 */
	//echo $query;
	$consulta = pg_query($query);
	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}

function combocat($tabela, $campos, $onde = null) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT $campos FROM $tabela WHERE $onde";
	//echo $query;
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_all($consulta);
	
	return $resultados;
}


function saldo($banco) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT (SELECT nome FROM banco WHERE codbanco=$banco) AS banco, SUM(CASE WHEN pagrec='P' THEN (valorpago*(-1)) ELSE valorpago END) AS saldo FROM lancamento WHERE codbanco = $banco";
	
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}


function geraid($tabela, $campo) {
	
	/**
	 * Montamos nossa query SQL para pegar apenas um dado
	 */
	$query = "SELECT MAX($campo)+1 AS newid FROM $tabela";
	
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}

function grafico($tipo, $filtro_ano, $filtro_mes, $filtro_categoria) {

	if (STRLEN($filtro_ano)>5) {
    	$filtro=$filtro_ano; 
	} else {
			$filtro="(EXTRACT(YEAR FROM CURRENT_DATE)) AND (EXTRACT(YEAR FROM CURRENT_DATE))";
	} 

	if (STRLEN($filtro_mes)>5) {
    	$filtro_mes=$filtro_mes; 
	} else {
			$filtro_mes="(EXTRACT(MONTH FROM CURRENT_DATE)) AND (EXTRACT(MONTH FROM CURRENT_DATE)) ";
	} 

	$categoria = "TRUE";
	if (STRLEN($filtro_categoria)>0) {
		$categoria ="lancamento.codcatlancto =". $filtro_categoria; 
	} 


	switch ($tipo) {
	case "SUBCATEGORIA":
    	$query = "SELECT json_agg(temp1), SUM(value) AS total  FROM (SELECT CASE WHEN SUBSTR(subcatlancto.descricao,2,1)='.' THEN SUBSTR(subcatlancto.descricao,3,9) ELSE subcatlancto.descricao END AS label, ROUND(SUM(CASE WHEN pagrec='R' THEN 			valorpago*(-1) ELSE valorpago END),0) AS value
    		,'j-showAlert-'||'codcatlancto='||lancamento.codcatlancto||' AND codsubcatlancto='||lancamento.codsubcatlancto|| ' AND (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro AND EXTRACT(MONTH FROM dtemissao) BETWEEN  $filtro_mes AND codcatlancto NOT IN (SELECT codcatlancto FROM catlancto WHERE debcred=''C'')' as link
					FROM lancamento 
					INNER JOIN subcatlancto ON (lancamento.codsubcatlancto = subcatlancto.codsubcatlancto) 
					INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
					WHERE  (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro
					AND EXTRACT(MONTH FROM dtemissao) BETWEEN $filtro_mes
					AND SUBSTR(catlancto.descricao,1,2)<>'X.' 
					AND $categoria
					GROUP BY 1,3 ORDER BY 2 DESC ) AS temp1";

		break;
	case "CATEGORIA":
		$query = "SELECT json_agg(temp1), SUM(value) AS total  FROM (
					
					SELECT CASE WHEN SUBSTR(catlancto.descricao,2,1)='.' THEN SUBSTR(catlancto.descricao,3,9) ELSE catlancto.descricao END AS label
					,ROUND(SUM(CASE WHEN pagrec='R' THEN 	valorpago*(-1) ELSE valorpago END),0) AS value
					,'j-showAlert-'||'codcatlancto='||lancamento.codcatlancto|| ' AND (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro AND EXTRACT(MONTH FROM dtemissao) BETWEEN $filtro_mes 
						AND codcatlancto NOT IN (SELECT codcatlancto FROM catlancto WHERE debcred=''C'') 
						AND $categoria' as link
					FROM lancamento 
					INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
					WHERE  (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro
					AND EXTRACT(MONTH FROM dtemissao) BETWEEN $filtro_mes
					AND SUBSTR(catlancto.descricao,1,2)<>'X.' 
					GROUP BY 1,3 ORDER BY 2 DESC 
					) AS temp1";

		break;
	case "ANO":
		$query = "SELECT json_agg(temp1), SUM(value) AS total  FROM (
					SELECT 'Ano:'||EXTRACT(YEAR FROM dtemissao) AS label, 
					ROUND(SUM(CASE WHEN pagrec='R' THEN valorpago*(-1) ELSE valorpago END),0) AS value
					,'j-showAlert-'||' (EXTRACT(YEAR FROM dtemissao))='||EXTRACT(YEAR FROM dtemissao)||' AND EXTRACT(MONTH FROM dtemissao) BETWEEN  $filtro_mes  
					AND codcatlancto NOT IN (SELECT codcatlancto FROM catlancto WHERE debcred=''C'') 
					AND $categoria'  as link
					FROM lancamento 
					INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
					WHERE  (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro
					AND EXTRACT(MONTH FROM dtemissao) BETWEEN $filtro_mes
					AND SUBSTR(catlancto.descricao,1,2)<>'X.'
					AND $categoria
					GROUP BY 1, EXTRACT(YEAR FROM dtemissao) ORDER BY 1
				) AS temp1";

		break;
	case "MES":
		$query = "SELECT json_agg(temp1), SUM(value) AS total  FROM (
					SELECT 'Mes:'||LPAD(CAST(EXTRACT(MONTH FROM dtemissao) AS CHAR(2)),2,'0') AS label, 
					ROUND(SUM(CASE WHEN pagrec='R' THEN valorpago*(-1) ELSE valorpago END),0) AS value 
					,'j-showAlert-'||' (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro'||' AND EXTRACT(MONTH FROM dtemissao)='||EXTRACT(MONTH FROM dtemissao)|| ' 
					AND codcatlancto NOT IN (SELECT codcatlancto FROM catlancto WHERE debcred=''C'')
					AND $categoria ' as link
					FROM lancamento 
					INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
					WHERE  (EXTRACT(YEAR FROM dtemissao)) BETWEEN $filtro
					AND EXTRACT(MONTH FROM dtemissao) BETWEEN $filtro_mes
					AND SUBSTR(catlancto.descricao,1,2)<>'X.'
					AND $categoria
					GROUP BY 1,EXTRACT(MONTH FROM dtemissao) ORDER BY 1 
				) AS temp1";

		break;
	}

	//echo $query;
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}


function grafico2($texto) {
	
	/*****
	 * Não esta em uso ainda
	 */
	$query = "SELECT json_agg(label) AS label,json_agg(value) AS value, json_agg(prev) AS prev FROM (

SELECT label, SUM(value) AS value, SUM(prev) AS prev FROM (
SELECT catlancto.descricao AS label, ROUND(SUM(CASE WHEN pagrec='R' THEN valorpago*(-1) ELSE valorpago END),0) AS value, 0 AS prev
FROM lancamento INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
WHERE  (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)   
AND SUBSTR(catlancto.descricao,1,2)<>'X.' GROUP BY 1 
UNION ALL
SELECT catlancto.descricao AS label, 0 AS value, ROUND(SUM(CASE WHEN pagrec='R' THEN valorpago*(-1) ELSE valorpago END)/12,0) AS prev
FROM lancamento INNER JOIN catlancto ON (lancamento.codcatlancto = catlancto.codcatlancto) 
WHERE  (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE))-1
AND SUBSTR(catlancto.descricao,1,2)<>'X.' GROUP BY 1 
) AS dados GROUP BY 1

) AS temp1";
	
	$consulta = pg_query($query);
	//echo $query;	
	/**
	 * Guardamos os resultados dentro do array resultados, que será retornado para a aplicação
	 */
	$resultados = pg_fetch_assoc($consulta);
	
	return $resultados;
}
