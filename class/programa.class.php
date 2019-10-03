<?php
require_once("websac/require_file.php");
require_file("class/cadastro.class.php");

class Programa extends Cadastro{
	function __construct($idtable = NULL){
		parent::__construct();
		$this->table = "programa";
		$this->primarykey = "idtable";
		$this->setidtable($idtable);
		if($this->getidtable() != NULL){
			$this->searchbyobject();
		}
	}

	function getidtable(){
		return $this->fields["idtable"];
	}

	function getnome(){
		return $this->fields["nome"];
	}

	function getlargura($format = FALSE){
		return ($format ? number_format($this->fields["largura"],2,",","") : $this->fields["largura"]);
	}

	function getcampos(){
		return $this->fields["campos"];
	}

	function getlinks(){
		return $this->fields["links"];
	}

	function getalinhamento(){
		return $this->fields["alinhamento"];
	}

	function getprograma(){
		return $this->fields["programa"];
	}

	function getparam(){
		return $this->fields["param"];
	}

	function getcodprograma(){
		return $this->fields["codprograma"];
	}

	function getcodigo(){
		return $this->fields["codigo"];
	}

	function getlinksprog(){
		return $this->fields["linksprog"];
	}

	function getdispmenu(){
		return $this->fields["dispmenu"];
	}

	function getproghelp(){
		return $this->fields["proghelp"];
	}

	function gettexto(){
		return $this->fields["texto"];
	}

	function getacessodireto(){
		return $this->fields["acessodireto"];
	}

	function getstringsql(){
		return $this->fields["stringsql"];
	}

	function getimgprog(){
		return $this->fields["imgprog"];
	}

	function getlargcoluna(){
		return $this->fields["largcoluna"];
	}

	function getwidth($format = FALSE){
		return ($format ? number_format($this->fields["width"],2,",","") : $this->fields["width"]);
	}

	function getheight($format = FALSE){
		return ($format ? number_format($this->fields["height"],2,",","") : $this->fields["height"]);
	}

	function getpopup(){
		return $this->fields["popup"];
	}

	function getesquerda($format = FALSE){
		return ($format ? number_format($this->fields["esquerda"],2,",","") : $this->fields["esquerda"]);
	}

	function gettopo($format = FALSE){
		return ($format ? number_format($this->fields["topo"],2,",","") : $this->fields["topo"]);
	}

	function getordem($format = FALSE){
		return ($format ? number_format($this->fields["ordem"],2,",","") : $this->fields["ordem"]);
	}

	function getnivel($format = FALSE){
		return ($format ? number_format($this->fields["nivel"],2,",","") : $this->fields["nivel"]);
	}

	function getcodprogpai(){
		return $this->fields["codprogpai"];
	}

	function getfecharmenu(){
		return $this->fields["fecharmenu"];
	}

	function getidprocesso(){
		return $this->fields["idprocesso"];
	}

	function getsenha(){
		return $this->fields["senha"];
	}

	function gettitulo(){
		return $this->fields["titulo"];
	}

	function getimagem(){
		return $this->fields["imagem"];
	}

	function setidtable($value){
		$this->fields["idtable"] = value_string($value,30);
	}

	function setnome($value){
		$this->fields["nome"] = value_string($value,40);
	}

	function setlargura($value){
		$this->fields["largura"] = value_numeric($value);
	}

	function setcampos($value){
		$this->fields["campos"] = value_string($value,100);
	}

	function setlinks($value){
		$this->fields["links"] = value_string($value,30);
	}

	function setalinhamento($value){
		$this->fields["alinhamento"] = value_string($value,50);
	}

	function setprograma($value){
		$this->fields["programa"] = value_string($value,60);
	}

	function setparam($value){
		$this->fields["param"] = value_string($value,60);
	}

	function setcodprograma($value){
		$this->fields["codprograma"] = value_string($value,10);
	}

	function setcodigo($value){
		$this->fields["codigo"] = value_string($value,60);
	}

	function setlinksprog($value){
		$this->fields["linksprog"] = value_string($value,100);
	}

	function setdispmenu($value){
		$this->fields["dispmenu"] = value_string($value,1);
	}

	function setproghelp($value){
		$this->fields["proghelp"] = value_string($value,60);
	}

	function settexto($value){
		$this->fields["texto"] = value_string($value,250);
	}

	function setacessodireto($value){
		$this->fields["acessodireto"] = value_string($value,1);
	}

	function setstringsql($value){
		$this->fields["stringsql"] = value_string($value,500);
	}

	function setimgprog($value){
		$this->fields["imgprog"] = value_string($value,80);
	}

	function setlargcoluna($value){
		$this->fields["largcoluna"] = value_string($value,60);
	}

	function setwidth($value){
		$this->fields["width"] = value_numeric($value);
	}

	function setheight($value){
		$this->fields["height"] = value_numeric($value);
	}

	function setpopup($value){
		$this->fields["popup"] = value_string($value,500);
	}

	function setesquerda($value){
		$this->fields["esquerda"] = value_numeric($value);
	}

	function settopo($value){
		$this->fields["topo"] = value_numeric($value);
	}

	function setordem($value){
		$this->fields["ordem"] = value_numeric($value);
	}

	function setnivel($value){
		$this->fields["nivel"] = value_numeric($value);
	}

	function setcodprogpai($value){
		$this->fields["codprogpai"] = value_string($value,30);
	}

	function setfecharmenu($value){
		$this->fields["fecharmenu"] = value_string($value,1);
	}

	function setidprocesso($value){
		$this->fields["idprocesso"] = value_string($value,15);
	}

	function setsenha($value){
		$this->fields["senha"] = value_string($value,1);
	}

	function settitulo($value){
		$this->fields["titulo"] = value_string($value,50);
	}

	function setimagem($value){
		$this->fields["imagem"] = value_string($value,50);
	}
}
?>