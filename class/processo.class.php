<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Processo extends Cadastro{

	function __construct($idprocesso = NULL){
		parent::__construct();
		$this->table = "processo";
		$this->primarykey = array("idprocesso");
		$this->setidprocesso($idprocesso);
		if(!is_null($this->getidprocesso())){
			$this->searchbyobject();
		}
	}

	function atualizar_intervalo(){
		$this->setdataexecucao(date("d/m/Y"));
		$this->sethoraexecucao(date("H:i:s"));
		if($this->save()){
			return TRUE;
		}else{
			$this->erro($_SESSION["ERROR"]);
			return FALSE;
		}
	}

	function erro($texto){
		$file = fopen("../proc/error.log", "a+");
		fwrite($file, date("d/m/Y H:i:s")." - ".$this->getidprocesso()."\r\n".$texto."\r\n\r\n");
		fclose($file);
	}

	function verificar_intervalo(){
		if(strlen($this->getintervalo()) == 0 || $this->getativo() != "S"){
			return FALSE;
		}
		if(strlen($this->getdataexecucao()) == 0 || strlen($this->gethoraexecucao()) == 0){
			return TRUE;
		}
		$data = explode("/", $this->getdataexecucao(TRUE));
		$hora = explode(":", $this->gethoraexecucao());
		$horaexecucao = mktime($hora[0], $hora[1], $hora[2], 0, 0, 0);
		$horaagora = mktime(date("H"), date("s"), date("i"), 0, 0, 0);
		if(strlen($this->gethorainicial()) > 0 && strlen($this->gethorafinal()) > 0){
			$hora = explode(":", $this->gethorainicial());
			$horainicial = mktime($hora[0], $hora[1], $hora[2], 0, 0, 0);
			$hora = explode(":", $this->gethorafinal());
			$horafinal = mktime($hora[0], $hora[1], $hora[2], 0, 0, 0);
			if(($horaagora < $horafinal && ($horaagora > $horainicial && $horaagora < $horafinal)) || ($horaagora > $horafinal && ($horaagora > $horainicial && $horaagora < $horafinal))){
				$data = explode("/", $this->getdataexecucao(TRUE));
				$hora = explode(":", $this->gethoraexecucao());
				$horaexecucao = mktime($hora[0], $hora[1], $hora[2], $data[1], $data[0], $data[2]);
				$diferenca = time() - $horaagora;
				return (($diferenca / 60) > $this->getintervalo());
			}else{
				return false;
			}
		}else{
			$horaexecucao = mktime($hora[0], $hora[1], $hora[2], $data[1], $data[0], $data[2]);
			$diferenca = time() - $horaexecucao;
			return (($diferenca / 60) > $this->getintervalo());
		}
	}

	function getidprocesso(){
		return $this->fields["idprocesso"];
	}

	function getintervalo(){
		return $this->fields["intervalo"];
	}

	function getdataexecucao($format = FALSE){
		return ($format ? convert_date($this->fields["dataexecucao"], "Y-m-d", "d/m/Y") : $this->fields["dataexecucao"]);
	}

	function gethoraexecucao(){
		return $this->fields["horaexecucao"];
	}

	function gethorainicial(){
		return $this->fields["horainicial"];
	}

	function gethorafinal(){
		return $this->fields["horafinal"];
	}

	function getativo(){
		return $this->fields["ativo"];
	}

	function getparametro(){
		return $this->fields["parametro"];
	}

	function setidprocesso($value){
		$this->fields["idprocesso"] = value_string($value, 20);
	}

	function setintervalo($value){
		$this->fields["intervalo"] = value_numeric($value);
	}

	function setdataexecucao($value){
		$this->fields["dataexecucao"] = value_date($value);
	}

	function sethoraexecucao($value){
		$this->fields["horaexecucao"] = value_string($value);
	}

	function sethorainicial($value){
		$this->fields["horainicial"] = value_string($value);
	}

	function sethorafinal($value){
		$this->fields["horafinal"] = value_string($value);
	}

	function setativo($value){
		$this->fields["ativo"] = value_string($value, 1);
	}

	function setparametro($value){
		$this->fields["parametro"] = value_string($value);
	}

}