<?php

require("../include/config.php");
$banco = listar("banco", "*");
$fornecedor = listar("fornecedor", "*");

if($_POST) {
    if (inserir("controle", $_POST)){
        header('Location: controle2.php');
    }
}

?>
<?php include('topo2.php'); ?>
<!--<script src="js/jquery.min.js" type="text/javascript"></script> Tirei porque esta chamada já esta no topo-->
<script src="js/jquery.maskMoney.js" type="text/javascript"></script>

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
						<input type="date" id="data" name="data" class="form-control col-6 col-xl-2 col-sm-10" value="<?php $month = date('m'); $day = date('d'); $year = date('Y'); echo $today = $year . '-' . $month . '-' . $day; ?>">
					</div>
				</div>
				
				<label>Fornecedor</label>
				<div>
					<div>
						<input type="text" id="fornecedor" name="fornecedor" list="fornec" class="form-control col-6 col-xl-8 col-sm-8">
							<datalist id="fornec">
								<?php  foreach ($fornecedor as $xfornecedor): ?>
								<option value="<?php echo $xfornecedor['codfornec'];?>"><?php echo $xfornecedor['nome'];?></option>
								<?php endforeach; ?>
							</datalist>	
					</div>
				</div>
				

				<label>Banco</label>
				<div>
					<div class="form-group">
						<select class="form-control" name="banco" id="banco">
							<?php  foreach ($banco as $xbanco): ?>
							 <option value="<?php echo $xbanco['codbanco'];?>"><?php echo $xbanco['nome'];?></option> 
							 <?php endforeach; ?>
						</select> 
					</div>
				</div>

				<label>Valor</label>
				<div>
					<div>
						<input type="decimal" inputmode="numeric" id="valor" name="valor" style="text-align:right;" class="form-control col-6 col-xl-8 col-sm-8" step="0.01">

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