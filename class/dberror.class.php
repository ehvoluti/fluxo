<?php
require_file("def/function.php");
require_file("class/connection.class.php");

class DbError{
	private $con;
	private $error;
	private $message;

	function __construct($con){
		$this->setconnection($con);
	}

	function getmessage(){
		if($this->error[0] == "P0001"){
			return $this->message;
		}
		return $this->message." ".utf8_encode($this->error[2]);
	}

	function setconnection($con){
		$this->con = $con;
		$this->seterror($this->con->errorInfo());
	}

	function seterror($error){
		if(!isset($this->con)){
			$this->con = new Connection();
		}
		$this->error = $error;
		if(in_array($this->error[0],array("23505"))){ // Cria uma conexao auxiliar para consulta ao banco de dados
			$con_aux = new Connection();
		}
		switch($this->error[0]){
			case "00000": // Nenhum erro

				break;
			case "P0001": // Erro enviado por funcoes do banco (raise exception)
				$message = substr($this->error[2],8);
				$pos = strpos($message,"CONTEXT:");
				if($pos !== FALSE){
					$message = substr($message,0,$pos - 1);
				}
				$this->message = $message;
				break;
			case "23503": // Chave da tabela referenciada em outra tabela (foreign key)
				$pos = strpos($this->error[2],"\"",0);
				$table1 = $this->table_name(substr($this->error[2],$pos + 1,strpos($this->error[2],"\"",$pos + 1) - $pos - 1));
				$table2 = substr($this->error[2],strpos($this->error[2],"DETAIL:"));
				$table2 = $this->table_name(substr($table2,strpos($table2,"\"") + 1,-2));
				if(substr($this->error[2],8,16) == "update or delete"){
					$this->message = "N&atilde;o foi poss&iacute;vel conclu&iacute;r a altera&ccedil;&atilde;o ou exclus&atilde;o do registro da tabela <b>".$table1."</b>, pois o mesmo encontra-se referenciado na tabela <b>".$table2."</b>.";
				}elseif(substr($this->error[2],8,16) == "insert or update"){
					$this->message = "N&atilde;o foi poss&iacute;vel conclu&iacute;r a inclus&atilde;o ou altera&ccedil;&atilde;o do registro da tabela <b>".$table1."</b>, pois a chave refer&ecirc;nciada da tabela <b>".$table2."</b> n&atilde;o existe.";
				}
				break;
			case "23505": // Campo duplicado em uma tabela (unique)
				$pos = strpos($this->error[2],"\"",0);
				$cons = $this->table_name(substr($this->error[2],$pos + 1,strpos($this->error[2],"\"",$pos + 1) - $pos - 1));
				$res = $con_aux->query("SELECT * FROM information_schema.constraint_column_usage WHERE constraint_name = '".$cons."'");
				$arr = $res->fetchAll(2);
				$columns = array();
				foreach($arr as $row){
					$columns[] = "<b>".$row["column_name"]."</b>";
				}
				if($row["table_name"] == "notafiscal"){
					$this->message = "Nota fiscal j&aacute; se encontra lan&ccedil;ada no sistema.";
				}
				else{
					$text = "Os campos (".implode(", ",$columns).") de valores (".implode(", ",$columns).") n&atilde;o podem ter seus valores repetidos ";
					$this->message = $text."na tabela <b>".$this->table_name($row["table_name"])."</b>.";
				}
				break;
            case "40P01":
                $this->message = "N&atilde;o foi poss&iacute;vel alterar o registro desejado, po&iacute;s o mesmo se encontra sendo alterado por outro usu&acute;rio. Tente a efetuar a opera&ccedil;&atilde;o novamente.";
                break;
			default: // Erro nao identificado
				$this->message = "Erro desconhecido.<br><br>".$this->error[2]."<br><b>(".$this->error[0].")</b>";
				break;
		}
//		echo $this->message;
	}

	private function table_name($table){
		switch($table){
			case "cliente": return "Cliente"; break;
			case "departamento": return "Departamento"; break;
			case "itnotafiscal": return "Itens de Nota Fiscal"; break;
			case "orcamento": return "Or&ccedil;amento"; break;
			case "produto": return "Produto"; break;
			default: return $table; break;
		}
	}
}
?>