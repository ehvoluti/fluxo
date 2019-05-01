<?php

require("../include/config.php");
$banco = listar("banco", "*");
$fornecedor = listar("fornecedor", "*");

if($_POST) {
    if (inserir("lancamentogru", $_POST)){
        header('Location: lancamento.php');
    }
}

?>
<?php include('topo.php'); ?>
<!--<script src="js/jquery.min.js" type="text/javascript"></script> Tirei porque esta chamada já esta no topo-->
<script src="js/jquery.maskMoney.js" type="text/javascript"></script>

	<!-- <script>
		function getFornec()
		{
			var d=document.getElementById("codparceiro");
			var displaytext = d.options[d.selectedIndex].text;
			document.getElementById("fornecedor").value=displaytext;
		}
		
	</script>
	-->

<div class="container">
	<form class="form-horizontal" action='' method="POST">
			<div id="legend">
				<legend class=""><h6>Fluxo de Caixa -> Lançamento</h6></legend>
			</div>
		<div class="row">
			<div class="col">	
			
				<label>Data</label>		
			   <div>
					<div>
						<input type="date" id="dtemissao" name="dtemissao" class="form-control col-6 col-xl-2 col-sm-10" value="<?php $month = date('m'); $day = date('d'); $year = date('Y'); echo $today = $year . '-' . $month . '-' . $day; ?>">
					</div>
				</div>
				
				<label>Fornecedor</label>
				<div class="row">
					<div class="col-4 form-group">
						<input type="text" id="codparceiro" name="codparceiro" list="fornec" class="form-control col-4 col-xl-4 col-sm-4 form-group" onchage="getFornec();">
							<datalist id="fornec" >
								<?php  foreach ($fornecedor as $xfornecedor): ?>
								<option value="<?php echo $xfornecedor['codfornec'];?>"><?php echo $xfornecedor['nome'];?></option>
								<?php endforeach; ?>
							</datalist>	
					</div>
					<!--
						<div class="col-8">
							<input type="text" id="fornecedor" name="fornecedor" readonly="true" class="form-control col-8 col-xl-8 col-sm-8 form-group">
						</div>
					-->
				</div>
				

				<label>Banco</label>
				<div>
					<div class="form-group">
						<select class="form-control" name="codbanco" id="banco">
							<?php  foreach ($banco as $xbanco): ?>
								<option value="<?php echo $xbanco['codbanco'];?>"><?php echo $xbanco['nome'];?></option> 
							<?php endforeach; ?>
						</select> 
					</div>
				</div>

				<label>Valor</label>
				<div>
					<div>
						<input type="decimal" inputmode="numeric" id="valor" name="valorbruto" style="text-align:right;" class="form-control col-6 col-xl-8 col-sm-8" step="0.01">
					</div>
				</div>
				
				
				<label>Referencia</label>
				<div>
					<div>
						<input type="text" id="referencia" name="referencia" class="form-control col-6 col-xl-8 col-sm-8">

					</div>
				</div>
				
				
				<br>
				<input type="submit" value="Incluir" class="btn btn-primary" >	
			</div>	
		</div>		
	</form>
			
</div>	

	<script type="text/javascript">
    $(function(){
        $("#valor").maskMoney();
    })
    </script>

<?php include("rodape.php"); ?>