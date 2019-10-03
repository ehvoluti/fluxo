<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class ModeloEmail extends Cadastro{
	function __construct($codmodeloemail = NULL){
		parent::__construct();
		$this->table = 'modeloemail';
		$this->primarykey = array("codmodeloemail");
		$this->setcodmodeloemail($codmodeloemail);
		if(!is_null($this->getcodmodeloemail())){
			$this->searchbyobject();
		}
	}

	function getcodmodeloemail(){
		return $this->fields["codmodeloemail"];
	}

	function gethost(){
		return $this->fields["host"];
	}

	function getusuario(){
		return $this->fields["usuario"];
	}

	function getsenha(){
		return $this->fields["senha"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function getcorpo(){
		return $this->fields["corpo"];
	}

	function getdescricao(){
		return $this->fields["descricao"];
	}

    function getporta(){
        return $this->fields["porta"];
    }

    function gettipoautenticacao(){
        return $this->fields["tipoautenticacao"];
    }

	function gettiposervidoremail(){
		return $this->fields["tiposervidoremail"];
	}

	function getprotocoloemail() {
		return $this->fields["protocoloemail"];
	}

	function getnomeremetente() {
		return $this->fields["nomeremetente"];
	}

	function getstatus() {
		return $this->fields["status"];
	}

	function gettipoenvio() {
		return $this->fields["tipoenvio"];
	}

	function setcodmodeloemail($value){
		$this->fields["codmodeloemail"] = value_numeric($value);
	}

	function sethost($value){
		$this->fields["host"] = value_string($value,60);
	}

	function setusuario($value){
		$this->fields["usuario"] = value_string($value,60);
	}

	function setsenha($value){
		$this->fields["senha"] = value_string($value,20);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,100);
	}

	function setcorpo($value){
		$this->fields["corpo"] = value_string($value);
	}

	function setdescricao($value){
		$this->fields["descricao"] = value_string($value,60);
	}

    function setporta($value){
        $this->fields["porta"] = value_numeric($value);
    }

    function settipoautenticacao($value){
		$this->fields["tipoautenticacao"] = value_string($value,5);
	}

	function settiposervidoremail($value){
		$this->fields["tiposervidoremail"] = value_string($value,4);
	}

	function setprotocoloemail($value) {
		$this->fields["protocoloemail"] = value_string($value,8);
	}

	function setnomeremetente($value) {
		$this->fields["nomeremetente"] = value_string($value,100);
	}

	function setativarautenticacao($value) {
		$this->fields["ativarautenticacao"] = value_string($value,1);
	}

	function setstatus($value) {
		$this->fields["status"] = value_string($value,1);
	}

	function settipoenvio($value){
		$this->fields["tipoenvio"] = value_string($value,1);
	}
}
