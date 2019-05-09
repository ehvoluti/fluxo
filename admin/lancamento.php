<?php
$filtro = '';
if ($_GET['filtrar_banco']) {
	$filtro=" AND codbanco=".$_GET['filtrar_banco'];
}	

require("../include/config.php");

$where = " (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)".$filtro;
$order = "dtemissao DESC";
$limit = "15";

$tipos = listar("lancamento", "*", $where , "" , $order, $limit);
$banco_listar = listar("banco", "codbanco, nome");

?>
<?php include("topo.php"); ?>
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
					<th data-toggle="modal" data-target="#conteudoModal">Banco</th>
					<!-- <th>Refer.</th> -->
					<th style="text-align: right">Valor</th>
					<th style="width: 36px;">Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($tipos as $tipo): ?>
					<tr>
						<th><?php echo SUBSTR($tipo['dtemissao'],8,2)."/".SUBSTR($tipo['dtemissao'],5,2); ?></th>
						<th data-toggle="tooltip" data-placement="left" title="<?php echo '['.$tipo['referencia'].']'; ?>"><?php echo SUBSTR($tipo['favorecido'],0,15)?></th>
							<?php $banco = listar("banco", "*", "codbanco=".$tipo['codbanco']);  foreach ($banco as $xbanco): ?>
								<th><?php echo $xbanco['nome'];?></th>
							<?php endforeach; ?>
						<!-- <th><?php // echo $tipo['codbanco']; ?></th> -->
						<!-- <th><?php //echo SUBSTR($tipo['referencia'],0,20); ?></th> -->
						<th style="text-align: right" ><?php echo number_format($tipo['valorparcela'],2); ?></th>
						<th>
							<!--icones-->
							<!--  <a href="alterarcontrole.php?id=<?php echo $tipo["codlancto"]; ?>"><i class="fas fa-edit"></i></a>  -->
							<a href="excluirLancamento.php?id=<?php echo $tipo["codlancto"]; ?>"><i class="fas fa-trash-alt"></i></a>
						</th>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	
	<!-- Rodapé da tabela
	<div class="row">
		<label>Filtro aplicado: <?//echo str_replace($where,'AND','e'). " ORDEM ". $order." LIMITE ".$limit?><label>
	</div>
	-->
</div>

<!-- Modal de busca -->
<form action="lancamento.php" method="get">
	<div id="conteudoModal" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Filtrar</h4>
					<button class="close" data-dismiss="modal" arial-label="Fechar">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				
				<div class="modal-body">
					<div class="row">
						<div class="col-6">
							<span>Campos</span>
						</div>	
						<div class="col-6">
								<select class="form-control" name="filtrar_banco" id="filtrar_banco">
									<option value="">-- Todos --</option> 
									<?php  foreach ($banco_listar as $xbanco): ?>
										<option value="<?php echo $xbanco['codbanco'];?>"><?php echo $xbanco['nome'];?></option> 
									<?php endforeach; ?>
								</select> 
						</div>
					</div>	
				</div>
				
				<div class="modal-footer">
					<input type="submit" value="Buscar" class="btn btn-primary" >
					<button class="btn btn-info" data-dismiss="modal">Fechar</button>
				</div>
			</div>
		</div>
	</div>
</form> 

<?php include("rodape.php"); ?>