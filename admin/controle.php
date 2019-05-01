<?php

require("../include/config.php");

$tipos = listar("controle", "*");

?>
<?php include("topo2.php"); ?>
<div class="container">
	<div class="row">
		<div class="span12">
			<legend class="">
				<h1>Controle</h1>
			</legend>
			<div class="btn-toolbar">
				<a href="incluirControle.php"><button class="btn btn-primary">Nova Controle</button></a>
			</div>
			<div class="well">
				<table width="100%" class="table">
					<col style="width:10%">
					<thead>
						<tr class="bold">
							<td>Data</td>
							<td>Fornec</td>
							<td>Banco</td>
							<td>Referencia</td>
							<td style="text-align: right">Valor</td>
							<td style="width: 36px;">Ações</td>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($tipos as $tipo): ?>
							<tr>
								<td><?php echo $tipo['data']; ?></td>
								<td><?php echo $tipo['fornecedor']; ?></td>
								<td><?php echo $tipo['banco']; ?></td>
								<td><?php echo $tipo['referencia']; ?></td>
								<td style="text-align: right"><?php echo $tipo['valor']; ?></td>
								<td>
									<!-- Refazer icones-->
									<a href="alterarCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="icon-pencil"></i></a>
									<a href="excluirCategoria.php?id=<?php echo $tipo["codcatlancto"]; ?>"><i class="icon-remove"></i></a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>	
<?php include("rodape.php"); ?>