<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Relatorio extends Cadastro{
	public $relatoriocoluna;
	public $relatoriofiltro;
	private $flag_relatoriocoluna = FALSE;
	private $flag_relatoriofiltro = FALSE;

	function __construct($codrelatorio = NULL){
		parent::__construct();
		$this->table = "relatorio";
		$this->primarykey = array("codrelatorio");
		$this->setcodrelatorio($codrelatorio);
		if(!is_null($this->getcodrelatorio())){
			$this->searchbyobject();
		}
	}

	function flag_relatoriocoluna($bool){
		if(is_bool($bool)){
			$this->flag_relatoriocoluna = $bool;
		}
	}

	function flag_relatoriofiltro($bool){
		if(is_bool($bool)){
			$this->flag_relatoriofiltro = $bool;
		}
	}

	function save($object = NULL){
		$this->connect();
		$this->con->start_transaction();
		$object = objectbytable("relatorio",$this->getcodrelatorio(),$this->con);
		if(parent::save($object)){
			// Coluna
			if($this->flag_relatoriocoluna){
				$relatoriocoluna = objectbytable("relatoriocoluna",NULL,$this->con);
				$relatoriocoluna->setcodrelatorio($this->getcodrelatorio());
				$arr_relatoriocoluna = object_array($relatoriocoluna);
				foreach($arr_relatoriocoluna as $relatoriocoluna){
					if(!$relatoriocoluna->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->relatoriocoluna as $relatoriocoluna){
					$relatoriocoluna->setcodrelatorio($this->getcodrelatorio());
					if(!$relatoriocoluna->save()){
						$this->con->rollback();
						return FALSE;
					}
				}
			}
			// Filtro
			if($this->flag_relatoriofiltro){
				$relatoriofiltro = objectbytable("relatoriofiltro",NULL,$this->con);
				$relatoriofiltro->setcodrelatorio($this->getcodrelatorio());
				$arr_relatoriofiltro = object_array($relatoriofiltro);
				foreach($arr_relatoriofiltro as $relatoriofiltro){
					if(!$relatoriofiltro->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				foreach($this->relatoriofiltro as $relatoriofiltro){
					$relatoriofiltro->setcodrelatorio($this->getcodrelatorio());
					if(!$relatoriofiltro->save()){
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
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1){
			if($this->flag_relatoriocoluna){
				$relatoriocoluna = objectbytable("relatoriocoluna",NULL,$this->con);
				$relatoriocoluna->setcodrelatorio($this->getcodrelatorio());
				$this->relatoriocoluna = object_array($relatoriocoluna);
			}
			if($this->flag_relatoriofiltro){
				$relatoriofiltro = objectbytable("relatoriofiltro",NULL,$this->con);
				$relatoriofiltro->setcodrelatorio($this->getcodrelatorio());
				$this->relatoriofiltro = object_array($relatoriofiltro);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		// Colunas
		$temporary = new Temporary("relatorio_relatoriocoluna",FALSE);
		$this->relatoriocoluna = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$relatoriocoluna = objectbytable("relatoriocoluna",NULL,$this->con);
			foreach($temporary->getcolumns() as $coluna){
				call_user_func(array($relatoriocoluna,"set".$coluna),$temporary->getvalue($i,$coluna));
			}
			$this->relatoriocoluna[] = $relatoriocoluna;
		}

		// Filtros
		$temporary = new Temporary("relatorio_relatoriofiltro",FALSE);
		$this->relatoriofiltro = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$relatoriofiltro = objectbytable("relatoriofiltro",NULL,$this->con);
			foreach($temporary->getcolumns() as $coluna){
				call_user_func(array($relatoriofiltro,"set".$coluna),$temporary->getvalue($i,$coluna));
			}
			$this->relatoriofiltro[] = $relatoriofiltro;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		// Colunas
		$temporary = new Temporary("relatorio_relatoriocoluna",TRUE);
		$temporary->setcolumns(array("coluna","titulo","tipo","alinhamento","largura","ordem","quebra","totalizar","visivel"));
		foreach($this->relatoriocoluna as $relatoriocoluna){
			$temporary->append();
			foreach($temporary->getcolumns() as $coluna){
				$temporary->setvalue("last",$coluna,call_user_func(array($relatoriocoluna,"get".$coluna),TRUE));
			}
		}
		$temporary->save();

		// Filtro
		$temporary = new Temporary("relatorio_relatoriofiltro",TRUE);
		$temporary->setcolumns(array("coluna","titulo","combobox","atributo","mascara","obrigatorio"));
		foreach($this->relatoriofiltro as $relatoriofiltro){
			$temporary->append();
			foreach($temporary->getcolumns() as $coluna){
				$temporary->setvalue("last",$coluna,call_user_func(array($relatoriofiltro,"get".$coluna),TRUE));
			}
		}
		$temporary->save();

		return $html;
	}

	function getcodrelatorio(){
		return $this->fields["codrelatorio"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

	function getinstrucao(){
		return $this->fields["instrucao"];
	}

	function getdatacriacao($format = FALSE){
		return ($format ? convert_date($this->fields["datacriacao"],"Y-m-d","d/m/Y") : $this->fields["datacriacao"]);
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getorientacao(){
		return $this->fields["orientacao"];
	}

	function getinstrucaocabecalho(){
		return $this->fields["instrucaocabecalho"];
	}

	function getdbtype(){
		return $this->fields["dbtype"];
	}

	function getdbuser(){
		return $this->fields["dbuser"];
	}

	function getdbpass(){
		return $this->fields["dbpass"];
	}

	function getdbhost(){
		return $this->fields["dbhost"];
	}

	function getdbname(){
		return $this->fields["dbname"];
	}

	function getdbport(){
		return $this->fields["dbport"];
	}

	function setcodrelatorio($value){
		$this->fields["codrelatorio"] = value_numeric($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

	function setinstrucao($value){
		$this->fields["instrucao"] = value_string($value);
	}

	function setdatacriacao($value){
		$this->fields["datacriacao"] = value_date($value);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setorientacao($value){
		$this->fields["orientacao"] = value_string($value);
	}

	function setinstrucaocabecalho($value){
		$this->fields["instrucaocabecalho"] = value_string($value);
	}

	function setdbtype($value){
		$this->fields["dbtype"] = value_string($value);
	}

	function setdbuser($value){
		$this->fields["dbuser"] = value_string($value);
	}

	function setdbpass($value){
		$this->fields["dbpass"] = value_string($value);
	}

	function setdbhost($value){
		$this->fields["dbhost"] = value_string($value);
	}

	function setdbname($value){
		$this->fields["dbname"] = value_string($value);
	}

	function setdbport($value){
		$this->fields["dbport"] = value_string($value);
	}
}
?>