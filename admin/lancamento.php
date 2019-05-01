<?php

require("../include/config.php");

$tipos = listar("lancamento", "*", "","" , "dtemissao DESC", "15");

?>
<?php include("topo2.php"); ?>
<div class="container">
	<legend>
		<h6>Fluxo de Caixa -> Lançamentos</h6>
	</legend>
	<div class="btn-toolbar">
		<a href="incluirLancamento.php"><button class="btn btn-primary">Novo</button></a>
	</div><br>
	<div class="form-group">
		<table class="table table-sm table-responsive-sm table-responsive-md">
			<thead class="thead-dark">
				<tr>
					<th>Dia/Mês</th>
					<th>Fornec/Ref</th>
					<th>Banco</th>
					<!-- <th>Refer.</th> -->
					<th style="text-align: right">Valor</th>
					<th style="width: 36px;">Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($tipos as $tipo): ?>
					<tr>
						<th><?php echo SUBSTR($tipo['dtemissao'],8,2)."/".SUBSTR($tipo['dtemissao'],5,2); ?></th>
						<th><?php echo SUBSTR($tipo['favorecido'],0,15)."(".SUBSTR($tipo['referencia'],0,18).")"; ?></th>
							<?php $banco = listar("banco", "*", "codbanco=".$tipo['codbanco']);  foreach ($banco as $xbanco): ?>
								<th><?php echo $xbanco['nome'];?></th>
							<?php endforeach; ?>
						<!-- <th><?php // echo $tipo['codbanco']; ?></th> -->
						<!-- <th><?php //echo SUBSTR($tipo['referencia'],0,20); ?></th> -->
						<th style="text-align: right" ><?php echo number_format($tipo['valorparcela'],2); ?></th>
						<th>
							<!--icones-->
							<a href="alterarcontrole.php?id=<?php echo $tipo["codlancto"]; ?>"><i class="fas fa-edit"></i></a>
							<a href="excluircontrole.php?id=<?php echo $tipo["codlancto"]; ?>"><i class="fas fa-trash-alt"></i></a>
						</th>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>	

<?php include("rodape.php"); ?>