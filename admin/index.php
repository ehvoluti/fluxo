<?php
include('../include/config.php');

//Verifica se está logado
if(!logado()){
    header('Location: login.php');
}

?>
<?php include('topo.php'); ?>
<div class="container-fluid" style="margin:20px">
	<div class="row">
		<div class="span12">
			<h1>Fluxo Pessoal</h1>
			<p>Fluxo de caixa para controle das despesas proprias</p>
			<a  href="incluirLancamento.php"><i class="fas fa-edit"></i>  Incluir Lançamento</a>
		</div>
	</div>
</div>	
<?php include("rodape.php"); ?>
