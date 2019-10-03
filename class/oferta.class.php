<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Oferta extends Cadastro{

	private $flag_itoferta = FALSE;
	private $flag_ofertaestab = FALSE;
	public $itoferta = array();	//contem os produtos da oferta
	public $ofertaestab = array(); //contem a(s) loja(s) qual pertence a(s) oferta(s)

	function __construct($codoferta = NULL){
		parent::__construct();
		$this->table = "oferta";
		$this->primarykey = array("codoferta");
		$this->newrelation("oferta", "codoferta", "ofertaestab", "codoferta");
		$this->setcodoferta($codoferta);
		$this->forcerelation = TRUE;
		if(!is_null($this->getcodoferta())){
			$this->searchbyobject();
		}
	}

	static function verificar_ofertas(Connection $con){
		// Cria log
		$arr_origem = array();
		$arr_backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
		foreach($arr_backtrace as $backtrace){
			$arr_origem[] = basename(dirname($backtrace["file"]))."/".basename($backtrace["file"]);
		}
		self::log("Iniciando verificacao de oferta.\r\nOrigem: ".implode(" > ", $arr_origem));

		$data = date("Y-m-d");
		$hora = date("H:i:s");

		$con->start_transaction();

		// Encerra as ofertas
		$res = $con->query("SELECT codoferta, usuario FROM oferta WHERE (datafinal < '".$data."' OR (datafinal = '".$data."' AND horafinal <= '".$hora."')) AND status = 'A'");
		$arr = $res->fetchAll(2);
		self::log("Ofertas a serem encerradas: ".count($arr));
		foreach($arr as $row){
			$oferta = objectbytable("oferta", $row["codoferta"], $con, true);
			$oferta->setstatus("E");
			$oferta->setusuario($row["usuario"]);
			if(!$oferta->save()){
				self::log("Erro ao encerrar oferta {$row["codoferta"]}:\r\n{$_SESSION["ERROR"]}");
				$con->rollback();
				return false;
			}
		}

		// Inicia as ofertas
		$res = $con->query("SELECT codoferta, usuario FROM oferta WHERE (datainicio < '".$data."' OR (datainicio = '".$data."' AND horainicio <= '".$hora."')) AND (datafinal > '".$data."' OR (datafinal = '".$data."' AND horafinal >= '".$hora."')) AND status = 'I'");
		$arr = $res->fetchAll(2);
		self::log("Ofertas a serem iniciadas: ".count($arr));
		foreach($arr as $row){
			$oferta = objectbytable("oferta", $row["codoferta"], $con, true);
			$oferta->setstatus("A");
			$oferta->setusuario($row["usuario"]);
			if(!$oferta->save()){
				self::log("Erro ao iniciar oferta {$row["codoferta"]}:\r\n{$_SESSION["ERROR"]}");
				$con->rollback();
				return false;
			}
		}

		self::log("Verificacao de oferta foi concluida.");

		$con->commit();
		return true;
	}

	static private function log($texto){
		$texto = date("Y-m-d H:i:s")."\r\n{$texto}\r\n\r\n";

		$filename = __DIR__."/../temp/oferta.log";

		if(filesize($filename) > 5000000){
			rename($filename, $filename.".old");
		}

		$file = fopen($filename, "a+");
		fwrite($file, $texto);
		fclose($file);
	}

	function flag_itoferta($b){
		if(is_bool($b)){
			$this->flag_itoferta = $b;
		}
	}

	function flag_ofertaestab($b){
		if(is_bool($b)){
			$this->flag_ofertaestab = $b;
		}
	}

	function save($object = null){
		$this->connect();
		if(compare_date($this->getdatafinal(), $this->getdatainicio(), "Y-m-d", "<")){
			$_SESSION["ERROR"] = "A data final n&atilde;o pode ser menor que a data inicial.";
			return FALSE;
		}
		if(is_null($object)){
			$object = objectbytable("oferta", $this->getcodoferta(), $this->con, true);
		}
		$this->con->start_transaction();
		if(parent::save($object)){
			if($this->flag_itoferta){
				if(sizeof($this->itoferta) == 0){
					$this->con->rollback();
					$_SESSION["ERROR"] = "&Eacute; preciso ter pelo menos um produto na promo&ccedil;&atilde;o.";
					return FALSE;
				}
				//pegar os codigos dos produtos da oferta para verificar se os mesmo ja estão em alguma  oferta cadastrada
				//que esteja ativa ou que ainda pode ser ativada
				$arr_codproduto = array();
				foreach($this->itoferta as $itoferta){
					$arr_codproduto[] = $itoferta->getcodproduto();
				}

				//pegar o(s) codigo(s) da(s) loja(s)
				$arr_codestabelec = array();
				foreach($this->ofertaestab as $ofertaestab){
					$arr_codestabelec[] = $ofertaestab->getcodestabelec();
				}

				if(sizeof($arr_codestabelec) == 0){
					$_SESSION["ERROR"] = "&Eacute; preciso ter pelo menos uma loja definida para gravar a oferta.";
					return FALSE;
				}
				$query = "SELECT oferta.codoferta, oferta.descricao, itoferta.codproduto, produto.descricaofiscal ";
				$query .= "FROM itoferta ";
				$query .= "INNER JOIN oferta ON (itoferta.codoferta = oferta.codoferta) ";
				$query .= "INNER JOIN produto ON (itoferta.codproduto = produto.codproduto) ";
				$query .= "INNER JOIN ofertaestab ON (oferta.codoferta = ofertaestab.codoferta)";
				$query .= "WHERE (to_timestamp('".$this->getdatainicio()." ".$this->gethorainicio()."','YYYY-MM-DD HH24:MI:SS') BETWEEN ";
				$query .= " to_timestamp(datainicio || ' ' || horainicio,'YYYY-MM-DD HH24:MI:SS') ";
				$query .= " AND to_timestamp(datafinal || ' ' || horafinal,'YYYY-MM-DD HH24:MI:SS') ";
				$query .= "OR to_timestamp('".$this->getdatafinal()." ".$this->gethorafinal()."','YYYY-MM-DD HH24:MI:SS') BETWEEN ";
				$query .= " to_timestamp(datainicio || ' ' || horainicio,'YYYY-MM-DD HH24:MI:SS') AND ";
				$query .= " to_timestamp(datafinal || ' ' || horafinal,'YYYY-MM-DD HH24:MI:SS')) ";
				$query .= "AND oferta.codoferta <> ".$this->getcodoferta()." AND oferta.status <> 'E' ";
				$query .= "AND itoferta.codproduto IN (".implode(",", $arr_codproduto).") ";
				$query .= "AND ofertaestab.codestabelec IN (".implode(",", $arr_codestabelec).") ";
				$query .= "AND oferta.tipopreco = '{$this->gettipopreco()}' ";
				$query .= "ORDER BY oferta.codoferta";

				$res = $this->con->query($query);
				$arr = $res->fetchAll(2);
				if(sizeof($arr) > 0){
					$codoferta = NULL;
					$ret = "";
					foreach($arr as $row){
						if(is_null($codoferta) || $codoferta != $row["codoferta"]){
							$ret .= "<b>Promo&ccedil;&atilde;o:</b> (".$row["codoferta"].") ".$row["descricao"]."<br>";
							$codoferta = $row["codoferta"];
						}
						$ret .= "(".$row["codproduto"].") ".$row["descricaofiscal"]."<br>";
					}
					$_SESSION["ERROR"] = "J&aacute; existe uma promo&ccedil;&atilde;o dentro do per&iacute;odo informado.<br><br>".$ret;
					$this->con->rollback();
					return FALSE;
				}

				$itoferta = objectbytable("itoferta", NULL, $this->con);
				$itoferta->setcodoferta($this->getcodoferta());
				$arr_itoferta = object_array($itoferta);
				foreach($arr_itoferta as $itoferta){
					if(!$itoferta->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->itoferta as $itoferta){
					$itoferta->setcodoferta($this->getcodoferta());
					if(!$itoferta->save()){
						$this->con->rollback();
						return FALSE;
					}
				}

				$ofertaestab = objectbytable("ofertaestab", NULL, $this->con);
				$ofertaestab->setcodoferta($this->getcodoferta());
				$arr_ofertaestab = object_array($ofertaestab);
				foreach($arr_ofertaestab as $ofertaestab){
					if(!$ofertaestab->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}

				foreach($this->ofertaestab as $ofertaestab){
					$ofertaestab->setcodoferta($this->getcodoferta());
					if(!$ofertaestab->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			$this->con->commit();
			return TRUE;
		}else{
			$this->con->rollback();
			return FALSE;
		}
	}

	function searchatdatabase($query, $fetchAll = FALSE){
		$return = parent::searchatdatabase($query, $fetchAll);
		if($return !== FALSE && sizeof($return) == 1){
			if($this->flag_itoferta){
				$itoferta = objectbytable("itoferta", NULL, $this->con);
				$itoferta->setcodoferta($this->getcodoferta());
				$this->itoferta = object_array($itoferta);
			}
			if($this->flag_ofertaestab){
				$ofertaestab = objectbytable("ofertaestab", NULL, $this->con);
				$ofertaestab->setcodoferta($this->getcodoferta());
				$this->ofertaestab = object_array($ofertaestab);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();
		$temporary = new Temporary("oferta_itoferta", FALSE);
		$this->itoferta = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$itoferta = objectbytable("itoferta", NULL, $this->con);
			foreach($temporary->getcolumns() as $column){
				call_user_func(array($itoferta, "set".$column), $temporary->getvalue($i, $column));
			}
			$this->itoferta[] = $itoferta;
		}

		$temporary = new Temporary("oferta_ofertaestab", FALSE);
		$this->ofertaestab = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$ofertaestab = objectbytable("ofertaestab", NULL, $this->con);
			foreach($temporary->getcolumns() as $column){
				call_user_func(array($ofertaestab, "set".$column), $temporary->getvalue($i, $column));
			}
			$this->ofertaestab[] = $ofertaestab;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		$temporary = new Temporary("oferta_itoferta", TRUE);
		$temporary->setcolumns(array("codproduto", "preco"));
		foreach($this->itoferta as $itoferta){
			$temporary->append();
			foreach($temporary->getcolumns() as $column){
				$temporary->setvalue("last", $column, call_user_func(array($itoferta, "get".$column), TRUE));
			}
		}
		$temporary->save();

		$temporary = new Temporary("oferta_ofertaestab", TRUE);
		$temporary->setcolumns(array("codestabelec"));
		foreach($this->ofertaestab as $ofertaestab){
			$temporary->append();
			foreach($temporary->getcolumns() as $column){
				$temporary->setvalue("last", $column, call_user_func(array($ofertaestab, "get".$column), TRUE));
			}
		}
		$temporary->save();

		return $html;
	}

	function getcodoferta(){
		return $this->fields["codoferta"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getdatainicio($format = FALSE){
		return ($format ? convert_date($this->fields["datainicio"], "Y-m-d", "d/m/Y") : $this->fields["datainicio"]);
	}

	function gethorainicio(){
		return $this->fields["horainicio"];
	}

	function getdatafinal($format = FALSE){
		return ($format ? convert_date($this->fields["datafinal"], "Y-m-d", "d/m/Y") : $this->fields["datafinal"]);
	}

	function gethorafinal(){
		return $this->fields["horafinal"];
	}

	function gettipopreco(){
		return $this->fields["tipopreco"];
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getdatalog($format = FALSE){
		return ($format ? convert_date($this->fields["datalog"], "Y-m-d", "d/m/Y") : $this->fields["datalog"]);
	}

	function setcodoferta($value){
		$this->fields["codoferta"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value, 60);
	}

	function setdatainicio($value){
		$this->fields["datainicio"] = value_date($value);
	}

	function sethorainicio($value){
		$this->fields["horainicio"] = value_string($value);
	}

	function setdatafinal($value){
		$this->fields["datafinal"] = value_date($value);
	}

	function sethorafinal($value){
		$this->fields["horafinal"] = value_string($value);
	}

	function settipopreco($value){
		$this->fields["tipopreco"] = value_string($value, 1);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value, 1);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value, 20);
	}

	function setdatalog($value){
		$this->fields["datalog"] = value_date($value);
	}

}
?>
