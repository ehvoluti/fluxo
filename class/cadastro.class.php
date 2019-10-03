<?php

require_file("def/function.php");
require_file("class/connection.class.php");
require_file("class/historico.class.php");
require_file("class/dberror.class.php");

abstract class Cadastro{

	protected $con; // (object) Objeto que contem a conexao com o banco de dados
	protected $extracolumns; // (string) Colunas extras que devem aparecer quando fizer uma pesquisa, etc
	protected $forcerelation; // (boolean) Variavel boleana que determina se nas pesquisas ele deve forcar o uso dos JOINs com as tabelas relacionadas
	protected $limit; // (integer) Valor inteiro determinando se existe um limite quando for pesquisar no banco de dados
	protected $order; // (string) Ordem dos registros para a pesquisa (formato sql)
	protected $primarykey; // (string/array) Chaves primarias da tabela
	protected $autoinc; // Se a chave primaria da tabela e de auto incremento
	protected $table; // (string) Nome da tabela
	protected $relation; // (array) Ligacao da tabela principal com tabelas secundarias. Usada para criacao dos JOINs nas pesquisas
	protected $arraysearch;
	protected $fieldarraysearch;

	function __construct(){
		$this->limit = NULL;
		$this->order = NULL;
		$this->forcerelation = FALSE;
		$this->relation = array();
		$this->autoinc = FALSE;
	}

	// Retorna uma string com uma condicao de uma coluna criada para ser usada em instrucoes SQL
	// Exemplo 1: $this->condition("produto","codproduto",5001)   => "produto.codproduto = 5001"
	// Exemplo 2: $this->condition("cliente","nome","silva",TRUE) => "UPPER(cliente.nome) LIKE UPPER('%silva%')"
	function condition($table, $field, $value, $parcialString = FALSE){
		// Verifica se e string e nao e chave primaria
		if($this->datatype($field, $table) == "string" && !((!is_array($this->primarykey) && $field == $this->primarykey) || (is_array($this->primarykey) && in_array($field, $this->primarykey)))){
			if($parcialString){
				$aux = "UPPER(".$table.".".$field.") LIKE UPPER('%".$value."%')";
			}else{
				$aux = $table.".".$field." = '".$value."'";
			}
			// Verifica se nao e string
		}else{ // E chave primaria do tipo string
			if(is_array($value)){
				$value_aux = array();
				foreach($value as $v){
					$value_aux[] = "'".$v."'";
				}
				$aux = $table.".".$field." IN (".implode(",", $value_aux).")";
			}elseif(strstr($field, ".")){
				$aux = $field." IN (".implode(",", $value_aux).")";
			}else{
				$aux = $table.".".$field." = '".$value."'";
			}
		}
		return $aux;
	}

	// Retorna TRUE se a coluna passada por parametro existir no banco de dados, senao retorna FALSE
	function columnexists($column){
		if((!is_array($this->getprimarykey()) && sizeof($this->fields) == 1) || (is_array($this->getprimarykey()) && sizeof($this->fields) == sizeof($this->getprimarykey()))){
			$columns = $this->getcolumnnames();
			foreach($columns as $column){
				if(!array_key_exists($column, $this->fields)){
					$this->fields[$column] = NULL;
				}
			}
		}
		return array_key_exists($column, $this->fields);
	}

	// Verifica se a classe ja se encontra conectada ao banco de dados, senao cria uma nova conexao
	protected function connect(){
		if(!isset($this->con)){
			$this->con = new Connection();
		}
	}

	// Retorno o tipo do campo passado por parametro
	// Exemplo 1: $this->datatype("codproduto") => "integer"
	// Exemplo 2: $this->datatype("nome")       => "string"
	function datatype($field, $table = NULL){
		$table = (is_null($table) ? $this->table : $table);

		if(strlen($_SESSION["cadastro"]["datatype"][$table][$field]) === 0){
			$this->connect();
			if($this->con->dbtype == "pgsql"){
				$query = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{$table}'";
				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				foreach($arr as $row){
					$type = $row["data_type"];
					switch($type){
						case "character varying":
						case "character":
						case "text":
							$type = "string";
							break;
					}
					$_SESSION["cadastro"]["datatype"][$table][$row["column_name"]] = $type;
				}
			}
		}
		return $_SESSION["cadastro"]["datatype"][$table][$field];
	}

	// Deleta o registro do banco de dados apartir da chave primaria informada na classe, retornando um valor boleano caso consiga apagar o registro ou nao
	function delete(){
		global $TabelasDB;
		$this->connect();

		$chave = array();
		if($this->exists()){
			$sql = "DELETE FROM ".$this->table." ";
			if(!is_array($this->primarykey)){
				$cond = "WHERE ".$this->primarykey." = '".$this->fields[$this->primarykey]."'";
				$excluido = $this->primarykey." = '".$this->fields[$this->primarykey]."'";
				$chave[] = $this->fields[$this->primarykey];
			}else{
				$cond = array();
				foreach($this->primarykey as $primarykey){
					$cond[] = "{$primarykey} = {$this->primarykey_value($primarykey)}";
					$chave[] = $this->fields[$primarykey];
				}
				$excluido = implode("\n", $cond);
				$cond = "WHERE ".implode(" AND ", $cond);
			}
			$sql .= $cond;

			$this->con->start_transaction();

			if(!$this->con->exec($sql)){
				$this->logsql($sql);
				$dberror = new DbError($this->con);
				$_SESSION["ERROR"] = $dberror->getmessage();
				$this->con->rollback();
				return FALSE;
			}

			if(!$this->historico($this)){
				$this->con->rollback();
				return FALSE;
			}

			$this->con->commit();
			return TRUE;
		}else{
			return TRUE;
		}
	}

	// Pesquisa o registro no banco de dados apartir da chave primaria informada, retornando TRUE caso exista ou FALSE caso contrario
	function exists(){
		$this->connect();
		if(!is_array($this->primarykey)){
			if($this->fields[$this->primarykey] != NULL){
				$flds = $this->primarykey;
				$cond = "WHERE ".$this->primarykey." = '".$this->fields[$this->primarykey]."'";
			}else{
				return FALSE;
			}
		}else{
			$cond = array();
			$flds = implode(", ", $this->primarykey);
			foreach($this->primarykey as $primarykey){
				if($this->fields[$primarykey] !== NULL){
					$cond[] = "{$primarykey} = {$this->primarykey_value($primarykey)}";
				}else{
					return FALSE;
				}
			}
			$cond = "WHERE ".implode(" AND ", $cond);
		}
		$query = "SELECT ".$flds." FROM ".$this->table." ".$cond;
		$res = $this->con->query($query);
			return ($res->rowCount() > 0);
		/*
		  if($res = @$this->con->query($query)){
		  return ($res->rowCount() > 0);
		  }else{
		  $this->logsql($query);
		  die("Erro ao executar a instru&ccedil;&atilde;o: ".$query);
		  }
		 */
	}

	// Forca a entrada de todos os JOINs contidos no atributo "relation" da classe quando for executar uma pesquisa
	function forcerelation($value){
		if(is_bool){
			$this->forcerelation = $value;
		}
	}

	// Retorna uma string no formato de objeto JavaScript, contendo o nome da coluna como indice do objeto e o valor do campo (elemento HTML) como cada valor do objeto
	function formvariables(){
		$arr = array();
		foreach($this->getcolumnnames() as $field){
			$aux = (count($arr) > 0 ? "&" : "");
			$arr[] = "\"".$field."\"".":$(\"#".$field."\").val()";
		}
		return implode(",", $arr);
	}

	// Retorna um array com o nome de todas as colunas da tabela referente a classe
	function getcolumnnames(){
		if(!is_array($_SESSION["cadastro"]["columns"][$this->table])){
			$this->connect();
			switch($this->con->dbtype){
				case "pgsql": $query = "SELECT column_name FROM information_schema.columns WHERE table_name = '".$this->table."'";
					break;
			}
			$res = $this->con->query($query);
			$arr = $res->fetchAll(2);
			$columns = array();
			foreach($arr as $row){
				$columns[] = $row["column_name"];
			}
			$_SESSION["cadastro"]["columns"][$this->table] = $columns;
		}
		return $_SESSION["cadastro"]["columns"][$this->table];
	}

	// Retorna a conexao que esta sendo usada na classe
	function getconnection(){
		$this->connect();
		return $this->con;
	}

	// Funcao utilizada para capturar os valores das variaveis armazenadas em $_REQUEST e atribuir no seu respectivo campo da classe
	// Exemplo: $_REQUEST["codproduto"]     =>    $this->setcodproduto($_REQUEST["codproduto"]);
	function getfieldvalues(){
		$columns = $this->getcolumnnames();
		foreach($columns as $column){
			if(array_key_exists($column, $_REQUEST) && /* strlen($_REQUEST[$column]) > 0 && */$_REQUEST[$column] != "undefined"){
				$value = $_REQUEST[$column];
				if(method_exists($this, "set{$column}")){
					call_user_func(array($this, "set{$column}"), $value);
				}
			}
		}
	}

	// Retorna o nome das chaves primarias da classe
	function getprimarykey(){
		return $this->primarykey;
	}

	// Retorna uma instrucao montada apartir dos campos informados na classe
	// Exemplo:
	//	$produto->setcodproduto(1);
	//	echo $produto->getquery();     =>     SELECT * FROM produto WHERE codproduto = 1;
	function getquery($limit = NULL, $offset = NULL, $addFields = NULL, $order = NULL, $parcialString = FALSE){
		$condition = array();
		$primarykey = $this->getprimarykey();

		// Verifica se a chave primaria foi informada dentro de um array
		if(!is_array($primarykey)){
			$primarykey = array($primarykey);
		}
		// Verifica se todas as chaves primarias foram preenchidas
		$foundNullPk = FALSE;
		foreach($primarykey as $key){
			if(strlen($this->fields[$key]) == 0){
				$foundNullPk = TRUE;
				break;
			}
		}
		// Percorre todos os campos da tabela para criar a condicao da instrucao SQL
		foreach($this->fields as $field => $value){
			if(strlen($value) > 0 && ($foundNullPk || in_array($field, $primarykey))){
				$condition[] = $this->condition($this->table, $field, $value, $parcialString);
			}
		}
		if(count($this->arraysearch) > 0){
			foreach($this->arraysearch as $i => $v){
				$condition[] = $this->condition($this->table, $i, $v, $parcialString);
			}
		}
		$q_from = "FROM ".$this->table." ";
		$q_join = "";
		foreach($this->relation as $relation){
			$found = FALSE;
			foreach($relation["values"] as $column => $value){
				if($value != NULL){
					$condition[] = $this->condition($relation["table2"], $column, $value, $parcialString);
					$found = TRUE;
				}
			}
			// Verifica se existe a chamada de alguma tabela secundaria nos campos adicionais
			if(!$found && strpos($addFields, $relation["table2_alias"]) !== FALSE){
				$found = TRUE;
			}
			if($found || $this->forcerelation){
				$join = array();
				for($i = 0; $i < sizeof($relation["column1"]); $i++){
					$join1 = (strpos($relation["column1"][$i], "'") === FALSE ? $relation["table1"].".".$relation["column1"][$i] : $relation["column1"][$i]);
					$join2 = (strpos($relation["column2"][$i], "'") === FALSE ? $relation["table2_alias"].".".$relation["column2"][$i] : $relation["column2"][$i]);
					$join[] = $join1." = ".$join2;
				}
				$q_join .= $relation["join"]." JOIN ".$relation["table2"]." AS ".$relation["table2_alias"]." ON (".implode(" AND ", $join).") ";
			}
		}
		$q_select = "SELECT ".(strlen($q_join) > 0 && strlen($this->order) == 0 ? "DISTINCT " : " ").$this->table.".*".($addFields != NULL ? ", ".$addFields : "")." ";
		if(sizeof($condition) > 0){
			$q_where = "WHERE ".implode(" AND ", $condition)." ";
		}else{
			$q_where = "";
		}
		if($this->order != NULL){
			$q_order = "ORDER BY ".$this->order." ";
		}elseif($order != NULL){
			$q_order = "ORDER BY ".$order." ";
		}else{
			$q_order = "";
		}
		$limit = ($limit == NULL ? $this->limit : $limit);
		$query = $q_select.$q_from.$q_join.$q_where.$q_order.($limit != NULL ? "LIMIT ".$limit : "").($offset != NULL ? "OFFSET ".$offset : "");
		return utf8_encode($query);
	}

	function gettablename(){
		return $this->table;
	}

	protected function historico(Cadastro $object_old = NULL, Cadastro $object_new = NULL){
		if(strlen($_SESSION["WUser"]) === 0){
			return TRUE;
		}

		$operacao = NULL;
		if(!is_null($object_old) && !is_null($object_new)){
			$operacao = "U"; // Update
		}elseif(!is_null($object_old)){
			$operacao = "D"; // Delete
		}elseif(!is_null($object_new)){
			$operacao = "I"; // Insert
		}

		if(in_array($operacao, array("D", "U"))){
			$tabela = $object_old->gettablename();
			$chave = $this->historico_chave($object_old);
		}else{
			$tabela = $object_new->gettablename();
			$chave = $this->historico_chave($object_new);
		}

		$arr_tabela = explode(", ", param("SISTEMA", "HISTORICO", $this->con));
		$arr_tabela = array_map("trim", $arr_tabela);

		if(!in_array($tabela, $arr_tabela)){
			return TRUE;
		}

		if(is_null($operacao)){
			$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel definir a opera&ccedil;&atilde;o para gerar o hist&oacute;rico.";
			return FALSE;
		}

		$historico = objectbytable("historico", NULL, $this->con);
		$historico->setdtcriacao(date("Y-m-d"));
		$historico->sethrcriacao(date("H:i:s"));
		$historico->setlogin($_SESSION["WUser"]);
		$historico->setoperacao($operacao);
		$historico->settabela($tabela);
		$historico->setchave($chave);
		$historico->setregistroold(is_null($object_old) ? NULL : $object_old->json());
		$historico->setregistronew(is_null($object_new) ? NULL : $object_new->json());

		return $historico->save();
	}

	protected function historico_chave(Cadastro $object){
		$arr_primarykey = $object->getprimarykey();
		if(!is_array($arr_primarykey)){
			$arr_primarykey = array($arr_primarykey);
		}
		$arr_chave = array();
		foreach($arr_primarykey as $primarykey){
			$arr_chave[] = call_user_func(array($object, "get".$primarykey));
		}
		$chave = implode("|", $arr_chave);
		return $chave;
	}

	function json($as_array = FALSE){
		$json = $this->fields;
		if(!$as_array){
			$json = json_encode($json);
		}
		return $json;
	}

	protected function logsql($sql){
//		$file = fopen("C:\\sql_log.txt","a+");
//		fwrite($file,date("d/m/Y - h:i:s")."\r\n".$sql."\r\n\r\n");
//		fclose($file);
	}

	function newprimarykey($min = NULL, $max = NULL, $key = NULL){
		$this->connect();
		$cond = array();
		if(!is_array($this->primarykey)){
			$primarykey = $this->primarykey;
		}else{
			foreach($this->primarykey as $primarykey){
				if($primarykey == NULL && $primarykey != $key){
					return NULL;
				}elseif($primarykey != $key){
					$cond[] = $primarykey." = '".$this->fields[$primarykey]."'";
				}
			}
		}
		if($min != NULL){
			$cond[] = $primarykey." >= ".$min;
		}
		if($max != NULL){
			$cond[] = $primarykey." <= ".$max;
		}
		if($this->autoinc){
			$query = "SELECT nextval('seq_".$this->table."_".$primarykey."')";
		}else{
			$query = "SELECT MAX(".$primarykey.") FROM ".$this->table;
			if(sizeof($cond) > 0){
				$query .= " WHERE ".implode(" AND ", $cond);
			}
		}
		if($res = $this->con->query($query)){
			$new = $res->fetchColumn();
			if(empty($new)){
				$new = ($min != NULL ? $min - 1 : 0);
			}
			if(is_numeric($new)){
				if($this->autoinc){
					return $new;
				}else{
					return ($new + 1);
				}
			}
		}else{
			return NULL;
		}
	}

	function newrelation($table1, $column1, $table2, $column2, $join = "LEFT", $values = array(), $table2_alias = NULL){
		if(!is_array($column1)){
			$column1 = array($column1);
		}
		if(!is_array($column2)){
			$column2 = array($column2);
		}
		if($table2_alias == NULL){
			$table2_alias = $table2;
		}
		foreach($this->relation as $i => $relation){
			if($relation["table1"] == $table1 && $relation["table2_alias"] == $table2_alias){
				$this->relation[$i]["column1"] = $column1;
				$this->relation[$i]["column2"] = $column2;
				return TRUE;
			}
		}
		$this->relation[] = array(
			"table1" => $table1,
			"column1" => $column1,
			"table2" => $table2,
			"column2" => $column2,
			"join" => $join,
			"values" => $values,
			"table2_alias" => $table2_alias
		);
		return TRUE;
	}

	function primarykey_value($primarykey){
		if(in_array($primarykey, array("idcupom"))){
			$value = sprintf("%.0F", $this->fields[$primarykey]);
		}else{
			$value = "'{$this->fields[$primarykey]}'";
		}
		return $value;
	}

	function save($object = NULL){
		$this->connect();

		// Captura o usuario da sessao
		$usuario = $_SESSION["WUser"];

		// Verifica se as chaves estao preenchidas, se nao estiver gera uma nova
		$arr_primarykey = $this->primarykey;
		if(!is_array($arr_primarykey)){
			$arr_primarykey = array($arr_primarykey);
		}
		$cond = array();
		foreach($arr_primarykey as $primarykey){
			if(is_null($this->fields[$primarykey])){
				$this->fields[$primarykey] = $this->newprimarykey(NULL, NULL, $primarykey);
			}
			if(is_null($this->fields[$primarykey])){
				$_SESSION["ERROR"] = "N&atilde;o foi poss&iacute;vel gerar uma nova chave para o campo <b>".$primarykey."</b> da tabela <b>".$this->table."</b>.";
				return FALSE;
			}
			$cond[] = "{$primarykey} = {$this->primarykey_value($primarykey)}";
		}
		$cond = implode(" AND ", $cond);

		// Preenche as colunas de usuario, data e hora para log quando houver
		$columns = $this->getcolumnnames();
		if(in_array("usuario", $columns) && !in_array($this->table, array("modeloemail")) && strlen($usuario) > 0){
			if(in_array($this->table, array("oferta"))){
				if(strlen($this->getusuario()) == 0){
					$this->setusuario($usuario);
				}
			}else{
				$this->setusuario($usuario);
			}
		}
		if(in_array("datalog", $columns)){
			$this->setdatalog(date("d/m/Y"));
		}
		if(in_array("horalog", $columns)){
			$this->sethoralog(date("H:i:s"));
		}

		// Abre transicao com o banco de dados
		$this->con->start_transaction();

		// Verifica se o registro existe, para criar ou alterar
		if($this->exists()){
			// Se nao foi passado o objeto por parametro, cria agora
			if(!is_object($object)){
				if(is_array($this->getprimarykey())){ // Chave composta
					$arrprimarykey = array();
					foreach($this->getprimarykey() as $primarykey){
						$arrprimarykey[] = $this->fields[$primarykey];
					}
					$object = objectbytable($this->table, $arrprimarykey, $this->con, true);
				}else{ // Chave simples
					$object = objectbytable($this->table, $this->fields[$this->getprimarykey()], $this->con, true);
				}
			}
			$arr_update = array();
			$arr_update_column = array();
			$arr_update_historico = array();
			foreach($this->fields as $field => $value){
				// Compara o tipo quando for um texto maior que 15, pois o PHP tem um bug que nao compara as variaveis direito
				// if((strlen($value) > 15 && $value !== $object->fields[$field]) || $value != $object->fields[$field]){
				//if(strcmp($value, $object->fields[$field]) !== 0){
				$igual = TRUE;
				if($this->datatype($field) === "string"){
					$igual = (strcmp($value, $object->fields[$field]) === 0);
				}else{
					$igual = ($value == $object->fields[$field]);
				}
				if(!$igual){
					$arr_update[] = $field." = ".(strlen($value) == 0 ? "NULL" : "'".str_replace("'", "''", $value)."'");
					$arr_update_column[] = $field;
					if(!in_array($field, array("usuario", "datalog", "horalog", "historico"))){
						$arr_update_historico[] = end($arr_update);
					}
				}
			}

			// Verifica se nao vai atualizar apenas o usuario, datalog e horalog
			if(!in_array($this->table, array("produto","natoperacao"))){
				$found = FALSE;
				$arr_columnlog = array("usuario", "datalog", "horalog");
				foreach($arr_update_column as $column){
					if(!in_array($column, $arr_columnlog)){
						$found = TRUE;
						break;
					}
				}
				if(!$found){
					$arr_update = array();
				}
			}

			if(count($arr_update) > 0){
				$sql = "UPDATE ".$this->table." SET ".implode(", ", $arr_update)." WHERE ".$cond;
				if(count($arr_update_historico) > 0 && strlen($usuario) > 0){
					$arr_chave = array();
					$arr_primarykey = (is_array($this->primarykey) ? $this->primarykey : array($this->primarykey));
					foreach($arr_primarykey as $primarykey){
						$arr_chave[] = $this->fields[$primarykey];
					}
				}
			}
		}else{
			unset($object);
			$arr_primarykey = $this->primarykey;
			if(!is_array($arr_primarykey)){
				$arr_primarykey = array($arr_primarykey);
			}
			foreach($this->fields as $field => $value){
				if(!is_array($value)){
					if($value != "NULL"){
						if(strlen($value) > 0){
							$value = "'".str_replace("'", "''", $value)."'";
						}
					}
					if(in_array($field, $arr_primarykey)){
						$value = $this->primarykey_value($field);
					}
					if(strlen($value) > 0){
						$nam[] = $field;
						$val[] = $value;
					}
				}
			}
			$sql = "INSERT INTO ".$this->table." (".implode(", ", $nam).") VALUES (".implode(", ", $val).") RETURNING *";
			//echo $sql; exit;
		}

		$sql = str_replace("\0", "", $sql);
		$sql = str_replace("\\", "\\\\", $sql);
//		echo $sql."<br>";
		if(strlen(trim($sql)) > 0){
			$res = $this->con->query($sql);
			if($res === FALSE){
				$dberror = new DbError($this->con);
				$error = $this->con->errorInfo();
				if($error[0] == "23505"){ // Trata erro de unique para exibir os valores que estao sendo repetidos
					$message = $dberror->getmessage();
					$arr_column = explode(",", substr($message, (strpos($message, "(") + 1), (strpos($message, ")") - strpos($message, "(") - 1)));

					$message_cliente = "Prezado cliente <b>{$this->table}</b> j&aacute; existe <br><span class=\"msg-erro-detalhe\" onclick=\"errodetalhe()\">ver detalhes</span> ";
					$message = "As colunas abaixo n&atilde;o podem ter seus valores repetidos na tabela <b>".$this->table."</b>:<br>";
					foreach($arr_column as $column){
						$column = str_replace(array(" ", "<b>", "</b>"), "", $column);
						$message .= $column." = ".call_user_func(array($this, "get".$column), TRUE)."<br>";
					}
					$message = $message_cliente."<div id=\"erro-tecnico\" >{$message}<br>{$sql}</div>";
				}else{
					$message = $dberror->getmessage();
				}

				$_SESSION["ERROR"] = $message;
				$this->con->rollback();
				return FALSE;
			}else{
				$row = $res->fetch(2);
				foreach($row as $column => $value){
					$this->fields[$column] = $value;
				}
			}
		}

		if(strlen(trim($sql)) > 0 && !$this->historico($object, $this)){
			$this->con->rollback();
			return FALSE;
		}

		$this->con->commit();
		return TRUE;
	}

	protected function searchatdatabase($query, $fetchAll = FALSE){
		$this->connect();
		if($res = $this->con->query($query)){
			if($fetchAll){
				$ret = array();
				$tab = $res->fetchAll(2); // apenas o nome das colunas
				foreach($tab as $i => $reg){
					foreach($reg as $j => $fil){
						$ret[$i][$j] = utf8_decode($fil);
					}
				}
				return $ret;
			}else{
				if($res->rowCount() == 0){
					return FALSE;
				}elseif($res->rowCount() == 1){
					$arr = $res->fetchAll(2);
					foreach($arr[0] as $field => $value){
						$this->fields[$field] = $value;
					}
					if(!is_array($this->primarykey)){
						return $this->fields[$this->primarykey];
					}else{
						$keys = array();
						foreach($this->primarykey as $primarykey){
							$keys[] = $this->fields[$primarykey];
						}
						return $keys;
					}
				}elseif($res->rowCount() > 1){
					if(!is_array($this->primarykey)){
						foreach($res->fetchAll(2) as $register){
							$keys[] = $register[$this->primarykey];
						}
						return $keys;
					}else{
						$keys = array();
						$i = 0;
						foreach($res->fetchAll(2) as $i => $register){
							foreach($this->primarykey as $primarykey){
								$keys[$i][] = $register[$primarykey];
							}
						}
						return $keys;
					}
				}
			}
		}else{
			$this->logsql($query);
			return FALSE;
		}
	}

	function searchbyobject($limit = NULL, $offset = NULL, $fetchAll = FALSE, $parcialString = FALSE){
		return $this->searchatdatabase($this->getquery($limit, $offset, NULL, NULL, $parcialString), $fetchAll);
	}

	function searchbyquery($query, $fetchAll = FALSE){
		return $this->searchatdatabase($query, $fetchAll);
	}

	function setconnection($connection){
		if(is_object($connection) || is_null($connection)){
			$this->con = $connection;
		}
	}

	function setfieldvalues(){
		$html = "";
		$arr_column = $this->getcolumnnames();
		foreach($arr_column as $column){
			// Excecoes
			if(in_array($this->table, array("notafiscal"))){
				if(in_array($column, array("xmlnfe"))){
					continue;
				}
			}
			/* comentado pois value_time não funciona
			$value = @call_user_func(array($this, "get".$column), FALSE);
			if(is_numeric($value) || value_date($value) !== NULL || value_time($value) !== NULL){
				$value = @call_user_func(array($this, "get".$column), TRUE);
			}
			*/
			$value = @call_user_func(array($this, "get".$column), TRUE);
			$value = str_replace("\\", "\\\\", $value);
			$value = str_replace("\"", "\\\"", $value);
			$value = textwarp($value);
			$html .= "$(\"#".$column."\").val(\"".$value."\"); ";
			if(in_array($value, array("S", "N"))){ // Tratamento para checkbox
				$html .= "$(\"#".$column."\").checked(".($value == "S" ? "true" : "false")."); ";
			}
		}
		return $html;
	}

	// Informa o limite de registros retornado pela query de pesquisa
	function setlimit($limit){
		$this->limit = $limit;
	}

	// Passar por parametro a ordem das colunas (exemplo: setorder("data","preco DESC") )
	function setorder(){
		$columns = func_get_args();
		$order = array();
		foreach($columns as $column){
			if(is_string($column)){
				if(strpos($column, ".") === FALSE && strpos($column, ",") === FALSE && strpos($column, "(") === FALSE){
					$order[] = $this->table.".".$column;
				}else{
					$order[] = $column;
				}
			}elseif(is_numeric($column)){
				$order[] = $column;
			}
		}
		if(sizeof($order) > 0){
			$this->order = implode(", ", $order);
		}else{
			$this->order = NULL;
		}
	}

	function setrelationvalue($table, $field, $value){
		foreach($this->relation as $key => $relation){
			if($relation["table2"] == $table){
				$this->relation[$key]["values"][$field] = $value;
			}
		}
	}

	function setarraysearch($table, $value){
		$this->arraysearch[$table] = $value;
	}

	function varheader(){
		$arr = array();
		$fields = $this->getcolumnnames();
		foreach($fields as $field){
			$aux = (sizeof($arr) > 0 ? "&" : "");
			$arr[] = "($('#".$field."').length > 0 ? '".$aux.$field."=' + textwarp($('#".$field."').val()) : '')";
		}
		return implode(" + ", $arr);
	}

}
