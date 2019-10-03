<?php
$filtro = '';
if ($_GET['filtrar_banco']) {
	$filtro=" AND codbanco=".$_GET['filtrar_banco'];
}

/*
if ($_GET['filtrar_referencia'] or $_GET['filtrar_valor_de'] or $_GET['filtrar_valor_ate']) {
	$filtro=" AND UPPER(referencia) LIKE UPPER('%".$_GET['filtrar_referencia']."%') AND valorparcela>=".$_GET['filtrar_valor_de']." AND valorparcela <=".$_GET['filtrar_valor_ate'];
}	
*/
if ($_GET['filtrar_referencia']) {
	$filtro=" AND UPPER(referencia) LIKE UPPER('%".$_GET['filtrar_referencia']."%')";
}

if($_GET['filtrar_valor_de']){
	$filtro .= " AND valorparcela>=".str_replace(',','',$_GET['filtrar_valor_de']);
}

if ($_GET['filtrar_valor_ate']) {
	$filtro .= " AND valorparcela <=".str_replace(',','',$_GET['filtrar_valor_ate']);
}

require("../include/config.php");

if (!$_GET){
	$where = " (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)".$filtro;	
} else {
	//$where = " (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)".$filtro;
	$where = "codlancto>0" .$filtro;

}
$order = "dtemissao DESC, codlancto DESC";
$limit = "150";

//echo $filtro."<br>";
$tipos = listar("lancamento", "*", $where , "" , $order, $limit);
$banco_listar = listar("banco", "codbanco, nome");

?>
<?php include("topo.php"); ?>

<script src="js/jquery.maskMoney.js" type="text/javascript"></script>

<div class="container">
	<legend>
		<h6>Fluxo de Caixa -> Lançamentos</h6>
	</legend>
	<div class="btn-toolbar">
		<a href="incluirLancamento.php"><button class="btn btn-primary">Novo</button></a>
		<button class="btn btn-primary" style=" margin-left:50px" data-toggle="modal" data-target="#filtrarModal">Mais Filtros</button>
	</div>
	<br>
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

<!-- Modal de busca para o Banco -->
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

<!-- Modal de busca Botão filtrar -->
<form action="lancamento.php" method="get">
	<div id="filtrarModal" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Filtrar na Grade</h4>
					<button class="close" data-dismiss="modal" arial-label="Fechar">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				
				<div class="modal-body">
					<!--Referencia-->
					<div class="row">
						<div class="col-4">
							<span>Referencia</span>
						</div>	
						<div class="col-8">
							<input type="text" id="filtrar_referencia" name="filtrar_referencia" class="form-control col-6 col-xl-8 col-sm-8">
						</div>
					</div>

					<!--Valores-->
					<div class="row">
						<div class="col-4">
							<span>Valor</span>
						</div>	
						<div class="col-4">De:
							<input type="decimal" id="filtrar_valor_de" name="filtrar_valor_de" class="form-control col-6 col-xl-8 col-sm-8" inputmode="numeric">
						</div>
						<div class="col-4">Ate:
							<input type="decimal" id="filtrar_valor_ate" name="filtrar_valor_ate" class="form-control col-6 col-xl-8 col-sm-8" inputmode="numeric" step="0.01">
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

	<script type="text/javascript">
    $(function(){
        $("#filtrar_valor_de").maskMoney();
        $("#filtrar_valor_ate").maskMoney();
    })
    </script>

<?php include("rodape.php"); ?>