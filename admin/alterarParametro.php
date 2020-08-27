<?php

require("../include/config.php");

if ($_POST) {
    alterar("parametro2", "idparametro={$_GET["id"]}",$_POST);
    header('Location: parametro.php');
 }

if($_GET["id"]){
    $tipo = ver("parametro2", "*", "idparametro={$_GET["id"]}");
}

?>
<?php include('topo2.php'); ?>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
  
			<legend class=""><h1>Alterar Parametro</h1></legend>

		<div class="row">
			<div class="col">	

				<label>#</label>		
			   <div>
					<div>
						<input type="text" id="idparametro" name="idparametro" class="form-control col-2 col-xl-4 col-sm-2 text-right" readonly value="<?php echo $tipo["idparametro"]; ?>">
					</div>
				</div>
			
				<label>Descrição</label>		
			   <div>
					<div>
						<input type="text" id="descrparam" name="descrparam" class="form-control col-6 col-xl-8 col-sm-8" readonly value="<?php echo $tipo["descrparam"]; ?>">
					</div>
				</div>
				
				<label>Observação</label>
				<div>
					<div>
						<input type="text" id="observacao" name="observacao" class="form-control col-6 col-xl-8 col-sm-8" readonly value="<?php echo $tipo["observacao"]; ?>">
					</div>
				</div>
				<label>Valor</label>
				<div>
					<div>
						<input type="text" id="valor" name="valor" class="form-control col-6 col-xl-8 col-sm-8" value="<?php echo $tipo["valor"]; ?>">
					</div>
				</div>				
				<br>
				<input type="submit" value="Alterar" class="btn btn-primary" >	
			</div>	
		</div>		
        </form>

</div>
<?php include("rodape.php"); ?>
