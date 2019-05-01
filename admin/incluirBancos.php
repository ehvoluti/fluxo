<?php

require("../include/config.php");

if($_POST) {
    if (inserir("banco", $_POST)){
        header('Location: bancos.php');
    }
}

?>
<?php include('topo.php'); ?>
<div class="container">
	<form class="form-horizontal" action='' method="POST">
			<div id="legend">
				<legend class=""><h1>Novo Banco</h1></legend>
			</div>
		<div class="row">
			<div class="col">	
			
				<label>Codigo</label>		
			   <div>
					<div>
						<input type="text" id="codbanco" name="codbanco" class="form-control col-4 col-xl-2 col-sm-8">
					</div>
				</div>
				
				<label>Nome</label>
				<div>
					<div>
						<input type="text" id="nome" name="nome" class="form-control col-6 col-xl-8 col-sm-8">

					</div>
				</div>
				<br>
				<input type="submit" value="Incluir" class="btn btn-primary" >	
			</div>	
		</div>		
	</form>
			
</div>	
<?php include("rodape.php"); ?>