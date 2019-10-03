<?php
require_once("websac/require_file.php");
require_file("class/notafiscaleletronicasocket.class.php");

class NFeMonitor {

	public $debug;
	private $nfesocket;
	private $pathAcbrNFE;
	private $pathAcbrCAN;
	private $pathAcbrINU;
	private $pathAcbrPDF;
	private $pathWebNFE;
	private $pathWebCAN;
	private $pathWebINU;
	private $pathWebPDF;

	public function __construct($host, $port) {
		$this->debug = false;
		$this->nfesocket = new NFeSocket($host, $port);

//		$this->pathAcbrNFE = "C:" . DS . "ACBrNFeMonitor" . DS . "web" . DS . "nfe" . DS;
//		$this->pathAcbrCAN = "C:" . DS . "ACBrNFeMonitor" . DS . "web" . DS . "can" . DS;
//		$this->pathAcbrINU = "C:" . DS . "ACBrNFeMonitor" . DS . "web" . DS . "inu" . DS;
//		$this->pathAcbrPDF = "C:" . DS . "ACBrNFeMonitor" . DS . "web" . DS . "pdf" . DS;
//
//		$this->pathWebNFE = "web" . DS . "nfe" . DS;
//		$this->pathWebCAN = "web" . DS . "can" . DS;
//		$this->pathWebINU = "web" . DS . "inu" . DS;
//		$this->pathWebPDF = "web" . DS . "pdf" . DS;
	}

	public function saveToFile($pathWeb) {
		$nome = basename($pathWeb);
		$conteudo = file_get_contents($pathWeb);

		$pathAcbr = $this->pathAcbrNFE . $nome;
		$arr = $this->nfesocket->execute( 'NFE.SAVETOFILE("'.$pathAcbr.'","'.$conteudo.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $pathAcbr;
		}

		return $arr['ERRO'];
	}

	public function loadFromFile($arquivo, $tipo= "xml") {
		$arr = $this->nfesocket->execute( 'NFE.LOADFROMFILE("'.$arquivo.'","'.$tipo.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			//$this->nfesocket->saveFile($pathWeb, $arr['OK']);
			//return $pathWeb;
			return $arr['OK'];
		}

		return $arr['ERRO'];
	}

	public function ativo() {
		$arr = $this->nfesocket->execute( 'NFE.ATIVO', $this->debug );
		if(is_bool($arr)){
			return $arr;
		}else{
			if (empty($arr['ERRO']) ) {
				return true;
			}
			return $arr['ERRO'];
		}
	}

	public function statusServico() {
		$arr = $this->nfesocket->execute( 'NFE.STATUSSERVICO', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}

		return $arr['ERRO'];
	}

	public function consultarNFe($chave) {
		$arr = $this->nfesocket->execute( 'NFE.CONSULTARNFE("'.$chave.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}

		return $arr['ERRO'];
	}

	public function cancelarNFe($chave, $justificativa='') {
		$arr = $this->nfesocket->execute( 'NFE.CANCELARNFE("'.$chave.'","'.$justificativa.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}

		return $arr['ERRO'];
	}

	public function imprimirDanfePDF($chave) {
		$arr = $this->nfesocket->execute( 'NFE.IMPRIMIRDANFEPDF("'.$chave.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $arr;
		}
		return $arr['ERRO'];
	}

	public function imprimirEventoPDF($eventonfe, $chavenfe, $anomes = ""){
		$arr = $this->nfesocket->execute('NFE.IMPRIMIREVENTOPDF("'.$eventonfe.'","'.$chavenfe.'","","","","'.$anomes.'")', $this->debug);
		if ( empty($arr['ERRO']) ) {
			return $arr;
			//return $this->nfesocket->formatReturn($arr["OK"]);
		}
		return $arr['ERRO'];
	}

	public function inutilizarNFe($cnpj, $justificativa, $serie, $numInicial, $numFinal, $modelo='55', $ano='') {
		$ano = ( $ano == '' ) ? date('y') : $ano;
		$arr = $this->nfesocket->execute('NFE.INUTILIZARNFE("'.$cnpj.'","'.$justificativa.'",'.$ano.','.$modelo.','.$serie.','.$numInicial.','.$numFinal.')', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}

		return $arr['ERRO'];
	}

	public function enviarNFe($chave, $lote, $assinar=1, $imprimir=0) {
		$pathAcbr = $this->pathAcbrNFE . $chave . ".xml";
		$arr = $this->nfesocket->execute( 'NFE.ENVIARNFE("'.$pathAcbr.'",'.$lote.','.$assinar.','.$imprimir.')', $this->debug );

		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}

		return $arr['ERRO'];
	}

	public function enviarNFeEmail($destinatario, $chavenfe, $enviarpdf = 1, $assunto = "", $copiasemails = "") {
		$arr = $this->nfesocket->execute( 'NFE.ENVIAREMAIL("'.$destinatario.'","'.$chavenfe.'",'.$enviarpdf.',"'.$assunto.'","'.$copiasemails.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return true;
		}
		return $arr['ERRO'];
	}

	public function enviarEmailEvento($destinatario, $evento, $chavenfe = "", $enviarpdf = 1, $assunto = "", $copiasemails = "", $anomes = ""){
		$arr = $this->nfesocket->execute('NFE.ENVIAREMAILEVENTO("'.$destinatario.'","'.$evento.'","'.$chavenfe.'",'.$enviarpdf.',"'.$assunto.'","'.$copiasemails.'","'.$anomes.'")', $this->debug );
		if(empty($arr["ERRO"])){
			return true;
		}
		return $arr["ERRO"];
	}

 	/**
	 * @return mixed boolean ou string com o erro
	 */
	public function downloadNFe($cnpj, $chave){
		$arr = $this->nfesocket->execute( 'NFE.DOWNLOADNFE("'.$cnpj.'","'.$chave.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}
		return $arr['ERRO'];
	}

	public function manifestoNFeDest($cnpj, $chave){
		$arr = $this->nfesocket->execute( 'NFE.MANIFESTONFE("'.$cnpj.'","'.$chave.'")', $this->debug );
		if ( empty($arr['ERRO']) ) {
			return $this->nfesocket->formatReturn( $arr['OK'] );
		}
		return $arr['ERRO'];
	}

	public function consultaNFeDest($cnpj, $indicadornfe, $indicadoremissor = 1, $cultimonsu = 0){
		$arr = $this->nfesocket->execute('NFE.CONSULTANFEDEST("'.$cnpj.'",'.$indicadornfe.','.$indicadoremissor.','.$cultimonsu.')', $this->debug);
		if(empty($arr['ERRO'])){
			return $this->nfesocket->formatReturn($arr['OK']);
		}
		return $arr['ERRO'];
	}

	public function enviarEvento($evento){
		$arr = $this->nfesocket->execute('NFE.ENVIAREVENTO("'.$evento.'")', $this->debug);
		if(empty($arr['ERRO'])){
			return $this->nfesocket->formatReturn($arr['OK']);
		}
		return $arr['ERRO'];
	}

	public function criarEnviarNfe($textoini, $lote, $imprimirdanfe = 0){
		$arr = $this->nfesocket->execute('NFE.CRIARENVIARNFE("'.$textoini.'",'.$lote.','.$imprimirdanfe.')', $this->debug);
		if(empty($arr['ERRO'])){
			return $this->nfesocket->formatReturn($arr['OK']);
		}
		return $arr['ERRO'];
	}

	public function imprimirDanfe($xmlnfe){
		$arr = $this->nfesocket->execute('NFE.IMPRIMIRDANFE("'.$xmlnfe.'")', $this->debug);
		if ( empty($arr['ERRO']) ) {
			return true;
		}
		return $arr['ERRO'];
	}

	public function imprimirEventoNFe($eventonfe, $anomes = ""){
		$arr = $this->nfesocket->execute('NFE.IMPRIMIREVENTO("'.$eventonfe.'","","","","","'.$anomes.'")', $this->debug);
		if ( empty($arr['ERRO']) ) {
			return true;
		}
		return $arr['ERRO'];
	}
}
?>
