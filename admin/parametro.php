<?php

require("../include/config.php");

$tipos = listar("parametro2", "*");

?>
<?php include("topo.php"); ?>
<div class="container">
	<legend>
		<h1>Parametros</h1>
	</legend>
	<div class="btn-toolbar">
		<!--<a href="incluirBancos.php"><button class="btn btn-primary">Novo</button></a> -->
	</div><br>
	<div class="form-group">
		<table class="table">
			<thead class="thead-dark">
				<tr>
					<th>Descrição</th>
					<th>Valor</th>
					<th style="width: 36px;">Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($tipos as $tipo): ?>
					<tr>
						<th><?php echo $tipo['descrparam']; ?></th>
						<th><?php echo $tipo['valor']; ?></th>
						<th>
							<a href="alterarParametro.php?id=<?php echo $tipo["idparametro"]; ?>"><i class="fas fa-edit"></i></a>
							<!-- <a href="excluirBancos.php?id=<?php echo $tipo["codbanco"]; ?>"><i class="fas  fa-trash-alt"></i></a> -->
						</th>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>	
<?php include("rodape.php"); ?>