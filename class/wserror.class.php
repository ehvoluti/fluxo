<?php
class WsError{
	private $number; // numero que identifica o erro
	private $is_error; // guarda valor booleano, para verificar se e um erro ou nao
	private $original; // mensagem original do erro
	private $message; // mensagem truduzida da original, para ser exibida para o usuario
	function __construct($number=NULL){
		$this->is_error(FALSE);
		$this->setnumber($number);
	}

	function finalize(){ // exibe na tela o as mensagens
		var_dump($this);
		die();
		die("<script type=\"text/javascript\"> window.location=\"../form/error.php?error=".rawurlencode(str_replace("\n","<br>",$this->getoriginal()))."\"; </script>");
	}

	function identifyoriginal(){ // transforma a mensagem original em uma em portugues
		$original = $this->original;
		if(substr($original,11,12) == "PDOException"){ // erro no PDO
			if(substr($original,59,29) == "could not translate host name"){ // host nao encontrado
				$this->setnumber(101);
			}elseif(substr($original,59,27) == "could not connect to server"){ // erro ao tentar conectar com o banco de dados
				$this->setnumber(102);
			}
		}elseif(substr($original,0,8) == "SQLSTATE"){ // erro de execucao sql no banco de dados
			switch(substr($original,9,5)){
				case "23503": $this->setnumber(201); break; // violacao de chave estrangeira
				case "42601": $this->setnumber(202); break; // erro de sintaxe
				case "42P01": $this->setnumber(203); break; // tabela nao existe
				case "42703": $this->setnumber(204); break; // campo nao existe na tabela
				case "23502": $this->setnumber(205); break; // campo nao aceita valor nulo
			}
		}else{
			$this->setnumber(999);
		}
		$this->is_error(TRUE);
	}

	function is_error($boolean=NULL){ // verifica se e um erro ou nao, se o parametro for passado como booleano ele apenas atribui o valor para classe, caso contrario retorna o valor
		if(is_bool($boolean)){
			$this->is_error = $boolean;
		}else{
			return $this->is_error;
		}
	}

	function mountmessage(){
		$original = $this->getoriginal();
		switch($this->number){
			case 999: $this->setmessage("Erro desconhecido:\n".$original); break;
			// erros de conexoes
			case 100: $this->setmessage("Nao foi possivel encontrar arquivo de configuracao do o banco de dados"); break;
			case 101: $this->setmessage("Nao foi possivel encontrar o host"); break;
			case 102: $this->setmessage("Nao foi possivel conectar com o banco de dados"); break;
			// erros de sql
			case 200: $this->setmessage("Registro nao encontrado na base de dados"); break;
			case 201: $this->setmessage("Existem registros da tabela ".getbetweenquotes($original,4)." que sao dependentes do registro da tabela ".getbetweenquotes($original,0)); break;
			case 202: $this->setmessage("Erro de sintaxe na instrucao SQL enviada ao banco de dados"); break;
			case 203: $this->setmessage("Tabela ".getbetweenquotes($original,0)." nao existe"); break;
			case 204: $this->setmessage("Campo ".getbetweenquotes($original,0)." nao existe na tabela ".getbetweenquotes($original,2)); break;
			case 205: $this->setmessage("Campo ".getbetweenquotes($original,0)." nao aceita valor nulo"); break;
		}
	}

	function getnumber(){
		return $this->number;
	}

	function getmessage(){
		return utf8_encode($this->message);
	}

	function getoriginal(){
		return $this->original;
	}

	function setnumber($number){
		$this->number = $number;
		if($this->getnumber() != NULL){
			$this->mountmessage();
		}
	}

	function setmessage($text){
		$this->message = $text;
	}

	function setoriginal($text){
		$this->original = $text;
		$this->identifyoriginal();
	}
}
?>