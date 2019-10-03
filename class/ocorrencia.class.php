<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Ocorrencia extends Cadastro{
	public $arr_subocorrencia;
	private $flag_subocorrencia = FALSE;

	function __construct($codocorrencia = NULL){
		parent::__construct();
		$this->table = "ocorrencia";
		$this->primarykey = array("codocorrencia");
		$this->setcodocorrencia($codocorrencia);
		if(!is_null($this->getcodocorrencia())){
			$this->searchbyobject();
		}
	}

	function flag_subocorrencia($bool){
		if(is_bool($bool)){
			$this->flag_subocorrencia = $bool;
		}
	}

	function save(){
		$this->connect();
		$this->con->start_transaction();
		if(parent::save()){
			if($this->flag_subocorrencia){
				// Verifica se existe pelo menos uma subocorrencia informada
				if(sizeof($this->arr_subocorrencia) == 0){
					$_SESSION["ERROR"] = "Informe ao menos uma subocorr&ecirc;ncia para a ocorr&ecirc;ncia atual.";
					$this->con->rollback();
					return FALSE;
				}
				// Apagar as subocorrencias
				$subocorrencia = objectbytable("subocorrencia",NULL,$this->con);
				$subocorrencia->setcodocorrencia($this->getcodocorrencia());
				$arr_subocorrencia = object_array($subocorrencia);
				foreach($arr_subocorrencia as $subocorrencia){
					if(!$subocorrencia->delete()){
						$this->con->rollback();
						return FALSE;
					}
				}
				// Grava as subocorrencias
				foreach($this->arr_subocorrencia as $subocorrencia){
					$subocorrencia->setcodocorrencia($this->getcodocorrencia());
					if(!$subocorrencia->save()){
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

	function searchatdatabase($query,$fetchAll = FALSE){
		$return = parent::searchatdatabase($query,$fetchAll);
		if($return !== FALSE && sizeof($return) == 1 && !$fetchAll){
			if($this->flag_subocorrencia){
				$subocorrencia = objectbytable("subocorrencia",NULL,$this->con);
				$subocorrencia->setcodocorrencia($this->getcodocorrencia());
				$this->arr_subocorrencia = object_array($subocorrencia);
			}
		}
		return $return;
	}

	function getfieldvalues(){
		parent::getfieldvalues();

		$temporary = new Temporary("ocorrencia_subocorrencia",FALSE);
		$arr_coluna = $temporary->getcolumns();
		$this->arr_subocorrencia = array();
		for($i = 0; $i < $temporary->length(); $i++){
			$subocorrencia = objectbytable("subocorrencia",NULL,$this->con);
			foreach($arr_coluna as $coluna){
				@call_user_func(array($subocorrencia,"set".$coluna),$temporary->getvalue($i,$coluna));
			}
			$subocorrencia->setcodocorrencia($this->getcodocorrencia());
			$this->arr_subocorrencia[] = $subocorrencia;
		}
	}

	function setfieldvalues(){
		$html = parent::setfieldvalues();

		$temporary = new Temporary("ocorrencia_subocorrencia",TRUE);
		$temporary->setcolumns(array("sequencial","codfunc","dtcriacao","hrcriacao","dtexecucao","hrexecucao","dtlimite","hrlimite","tarefa","conclusao"));
		$arr_coluna = $temporary->getcolumns();
		array_shift($arr_coluna); // Remove o campo sequencial, que serve apenas para controle na tela
		foreach($this->arr_subocorrencia as $i => $subocorrencia){
			$temporary->append();
			$temporary->setvalue("last","sequencial",($i + 1));
			foreach($arr_coluna as $coluna){
				$temporary->setvalue("last",$coluna,utf8_encode(@call_user_func(array($subocorrencia,"get".$coluna))));
			}
		}
		$temporary->save();

		return $html;
	}

	function getcodocorrencia(){
		return $this->fields["codocorrencia"];
	}

	function gettipoparceiro(){
		return $this->fields["tipoparceiro"];
	}

	function getcodparceiro(){
		return $this->fields["codparceiro"];
	}

	function getcodgrupoocor(){
		return $this->fields["codgrupoocor"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function getobservacao(){
		return $this->fields["observacao"];
	}

	function getcodfunc(){
		return $this->fields["codfunc"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"],"Y-m-d","d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"],0,8);
	}

	function getdtconclusao($format = FALSE){
		return ($format ? convert_date($this->fields["dtconclusao"],"Y-m-d","d/m/Y") : $this->fields["dtconclusao"]);
	}

	function gethrconclusao(){
		return substr($this->fields["hrconclusao"],0,8);
	}

	function getstatus(){
		return $this->fields["status"];
	}

	function getdtlimite($format = FALSE){
		return ($format ? convert_date($this->fields["dtlimite"],"Y-m-d","d/m/Y") : $this->fields["dtlimite"]);
	}

	function gethrlimite(){
		return substr($this->fields["hrlimite"],0,8);
	}

	function getcodproduto(){
		return $this->fields["codproduto"];
	}

	function setcodocorrencia($value){
		$this->fields["codocorrencia"] = value_numeric($value);
	}

	function settipoparceiro($value){
		$this->fields["tipoparceiro"] = value_string($value,1);
	}

	function setcodparceiro($value){
		$this->fields["codparceiro"] = value_numeric($value);
	}

	function setcodgrupoocor($value){
		$this->fields["codgrupoocor"] = value_numeric($value);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,60);
	}

	function setobservacao($value){
		$this->fields["observacao"] = value_string($value);
	}

	function setcodfunc($value){
		$this->fields["codfunc"] = value_numeric($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setdtconclusao($value){
		$this->fields["dtconclusao"] = value_date($value);
	}

	function sethrconclusao($value){
		$this->fields["hrconclusao"] = value_time($value);
	}

	function setstatus($value){
		$this->fields["status"] = value_string($value,1);
	}

	function setdtlimite($value){
		$this->fields["dtlimite"] = value_date($value);
	}

	function sethrlimite($value){
		$this->fields["hrlimite"] = value_time($value);
	}

	function setcodproduto($value){
		$this->fields["codproduto"] = value_numeric($value);
	}
}
?>