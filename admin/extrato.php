<?php
/*--------------------
	Hugo 01/09/2020
--------------------*/	
$filtro = '';
if ($_GET['filtrar_banco']) {
	$filtro=" AND codbanco=".$_GET['filtrar_banco'];
} else {
	$filtro=" AND codbanco=4";
	$_GET['filtrar_banco']="4";
}


if ($_GET['dtliquid1']) {
	$filtro=$filtro." AND dtliquid>='".$_GET['dtliquid1']."'";
	$_GET['dtliquid1']="CAST('".$_GET['dtliquid1']."' AS DATE)-1";
} else {
		$_GET['dtliquid1']="CAST(CURRENT_DATE AS DATE)-1";
		$filtro=$filtro. " AND (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)";
}

if ($_GET['dtliquid2']) {
	$filtro=$filtro." AND dtliquid<='".$_GET['dtliquid2']."'";
} 


/*
if ($_GET['filtrar_referencia']) {
	$filtro=" AND UPPER(referencia) LIKE UPPER('%".$_GET['filtrar_referencia']."%')";
}

if($_GET['filtrar_valor_de']){
	$filtro .= " AND valorparcela>=".str_replace(',','',$_GET['filtrar_valor_de']);
}

if ($_GET['filtrar_valor_ate']) {
	$filtro .= " AND valorparcela <=".str_replace(',','',$_GET['filtrar_valor_ate']);
}

*/
require("../include/config.php");

if (!$_GET){
	$where = " (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)".$filtro;	
} else {
	//$where = " (EXTRACT(YEAR FROM dtemissao))=(EXTRACT(YEAR FROM CURRENT_DATE)) AND EXTRACT(MONTH FROM dtemissao)=EXTRACT(MONTH FROM CURRENT_DATE)".$filtro;
	$where = "codlancto>0" .$filtro;
}



$fields = "dtliquid AS data
			,favorecido
		 	,referencia
		   ,CASE WHEN pagrec='P' THEN valorpago*(-1) ELSE 0 END AS debito 
		   ,CASE WHEN pagrec='R' THEN valorpago ELSE 0 END AS credito";
			

$order = "dtliquid ASC";
$limit = "350";

//echo $filtro."<br>";
$tipos = listar("lancamento", $fields, $where , "" , $order, $limit);
$banco_listar = listar("banco", "codbanco, nome");
//$dia_ant = strtotime($_GET['filtrar_banco']);
$busca_saldo = saldo($_GET['filtrar_banco'], $_GET['dtliquid1']);
$saldo = $busca_saldo['saldo'];

?>
<?php include("topo.php"); ?>

<script src="js/jquery.maskMoney.js" type="text/javascript"></script>

<div class="container">
	<legend>
		<h6>Relatórios -> Extrato</h6>
	</legend>
	<div class="btn-toolbar">
		<button class="btn btn-primary" style=" margin-left:50px" data-toggle="modal" data-target="#filtrarModal">Filtros</button>
	</div>
	<br>
	<div class="form-group">
		<table class="table table-sm table-responsive-sm table-responsive-md">
			<thead class="thead-dark">
				<tr>
					<th>Data</th>
					<th>Fornec/Ref</th>
					<!-- <th data-toggle="modal" data-target="#conteudoModal">Banco</th> -->
					<!-- <th>Refer.</th> -->
					<th style="text-align: right">Crédito</th>
					<th style="text-align: right">Debito</th>
					<th style="text-align: right">Saldo</th>
				</tr>
			</thead>
			<tbody>
					<tr>
						<th>Saldo Inicial:</th>
						<th>...</th>

						<th></th>
						<th></th>
						<th style="text-align: right" ><?php echo  number_format($saldo,2)  ?></th>
					</tr>
				<?php foreach ($tipos as $tipo): ?>
					<tr>
						<th><?php echo $tipo['data']; ?></th>
						<th data-toggle="tooltip" data-placement="left" title="<?php echo '['.$tipo['referencia'].']'; ?>"><?php echo SUBSTR($tipo['favorecido'],0,15)?></th>

						<th style="text-align: right" ><?php echo number_format($tipo['debito'],2); ?></th>
						<th style="text-align: right" ><?php echo number_format($tipo['credito'],2); $saldo +=  $tipo['credito']+$tipo['debito'];  ?></th>
						<th style="text-align: right" ><?php echo  number_format($saldo,2)  ?></th>
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



<!-- Modal de busca Botão filtrar -->
<form action="extrato.php" method="get">
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
					<!--Datas-->
					<div class="row">
						<div class="col-12">
							<span>Data</span>
						</div>	
						<div class="col-6">	
							<input type="date" id="dtliquid1" name="dtliquid1" class="form-control" value="<?php $month = date('m'); $day = date('d'); $year = date('Y'); echo $today = $year . '-' . $month . '-' . $day; ?>">
						</div>
						<div class="col-6">	
							<input type="date" id="dtliquid2" name="dtliquid2" class="form-control" value="<?php $month = date('m'); $day = date('d'); $year = date('Y'); echo $today = $year . '-' . $month . '-' . $day; ?>">
						</div>
					</div>

					<!--Banco-->
					<div class="row-1">
						<div class="col-12">
							<span>Banco</span>
						</div>
						<div class="col-6">	
							<select class="form-control" name="filtrar_banco" id="filtrar_banco" class="form-control col-6 col-xl-6 col-sm-10">
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

	<script type="text/javascript">
    $(function(){
        $("#filtrar_valor_de").maskMoney();
        $("#filtrar_valor_ate").maskMoney();
    })
    </script>

<?php include("rodape.php"); ?>