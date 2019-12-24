<?php
include('../include/config.php');

?>
<?php include('topo.php'); ?>
<!-- Acesso rapido -->
<div class="container-fluid" style="margin:20px">
	<div class="row">
		<div class="span12">
			<h1>Dashboards</h1>
			
			<a  href="incluirLancamento.php"><i class="fas fa-edit"></i>Incluir Lançamento</a>
		</div>
	</div>
	<!-- Mostra Saldo 
	<div class="row">
		<div class="span12">
		<i class="fas fa-wallet" onclick="versaldo(4);"></i>
		<i class="fas fa-credit-card" onclick="versaldo(5);"></i>
		<span id="saldo4"></span>
		</div>
	</div> -->
	<!-- Grafico Inicial -->
	
	<div>
		<?php include('../campari/grf_categoria.php');?>
	</div>
</div>



<?php include("rodape.php"); ?>
