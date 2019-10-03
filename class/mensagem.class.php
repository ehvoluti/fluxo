<?php

require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Mensagem extends Cadastro{

	function __construct($codmensagem = NULL){
		parent::__construct();
		$this->table = "mensagem";
		$this->primarykey = array("codmensagem");
		$this->setcodmensagem($codmensagem);
		if(!is_null($this->getcodmensagem())){
			$this->searchbyobject();
		}
	}

	function incluir_mensagem($codtipomensagem, $tabelaref, $chaveref, $titulo, $texto, $acao = null, $forcar = false){
		// Verifica se a mensagem ja existe
		$mensagem = new Mensagem();
		$mensagem->setconnection($this->con);
		$mensagem->setlogin($_SESSION["WUser"]);
		$mensagem->setcodtipomensagem($codtipomensagem);
		$mensagem->settabelaref($tabelaref);
		$mensagem->setchaveref($chaveref);
		$arr_mensagem = object_array($mensagem);
		if(count($arr_mensagem) > 0){
			if($forcar){
				$mensagem = array_shift($arr_mensagem);
				if($mensagem->getdtcriacao() === date("Y-m-d")){
					return true;
				}
			}else{
				return true;
			}
		}

		// Preenche os campos restantes da mensagem
		$mensagem->setdtcriacao(date("Y-m-d"));
		$mensagem->sethrcriacao(date("H:i:s"));
		$mensagem->settitulo($titulo);
		$mensagem->settexto($texto);
		$mensagem->setacao($acao);
		$mensagem->setlido("N");

		// Grava a mensagem
		if($mensagem->save()){
			return true;
		}else{
			return false;
		}
	}

	function save($object = null){
		// Verifica se mensagem nao existe
		/*$query = "SELECT COUNT(codmensagem) FROM mensagem WHERE login = '{$this->getlogin()}' AND codtipomensagem = '{$this->getcodtipomensagem()}' AND tabelaref = '{$this->gettabelaref()}' AND chaveref = '{$this->getchaveref()}'";
		$res = $this->con->query($query);
		$count = $res->fetchColumn();
		if($count > 0){
			return true;
		}*/

		// Grava a mensagem
		return parent::save($object);
	}

	function getcodmensagem(){
		return $this->fields["codmensagem"];
	}

	function getlogin(){
		return $this->fields["login"];
	}

	function getcodtipomensagem(){
		return $this->fields["codtipomensagem"];
	}

	function gettabelaref(){
		return $this->fields["tabelaref"];
	}

	function getchaveref(){
		return $this->fields["chaveref"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function gettexto(){
		return $this->fields["texto"];
	}

	function getacao(){
		return $this->fields["acao"];
	}

	function getdtcriacao($format = FALSE){
		return ($format ? convert_date($this->fields["dtcriacao"], "Y-m-d", "d/m/Y") : $this->fields["dtcriacao"]);
	}

	function gethrcriacao(){
		return substr($this->fields["hrcriacao"], 0, 8);
	}

	function getlido(){
		return $this->fields["lido"];
	}

	function getemailenviado(){
		return $this->fields["emailenviado"];
	} 

	function setcodmensagem($value){
		$this->fields["codmensagem"] = value_numeric($value);
	}

	function setlogin($value){
		$this->fields["login"] = value_string($value, 20);
	}

	function setcodtipomensagem($value){
		$this->fields["codtipomensagem"] = value_numeric($value);
	}

	function settabelaref($value){
		$this->fields["tabelaref"] = value_string($value, 40);
	}

	function setchaveref($value){
		$this->fields["chaveref"] = value_string($value, 100);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value, 60);
	}

	function settexto($value){
		$this->fields["texto"] = value_string($value);
	}

	function setacao($value){
		$this->fields["acao"] = value_string($value);
	}

	function setdtcriacao($value){
		$this->fields["dtcriacao"] = value_date($value);
	}

	function sethrcriacao($value){
		$this->fields["hrcriacao"] = value_time($value);
	}

	function setlido($value){
		$this->fields["lido"] = value_string($value, 1);
	}

	function setemailenviado($value){
		$this->fields["emailenviado"] = value_string($value, 1);
	}
}