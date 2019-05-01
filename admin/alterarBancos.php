<?php

require("../include/config.php");

if ($_POST) {
    alterar("banco", "codbanco={$_GET["id"]}",$_POST);
    header('Location: bancos.php');
 }

if($_GET["id"]){
    $tipo = ver("banco", "*", "codbanco={$_GET["id"]}");
}

?>
<?php include('topo2.php'); ?>
<div class="container">
        <form class="form-horizontal" action='' method="POST">
  
			<legend class=""><h1>Alterar Bancos</h1></legend>

		<div class="row">
			<div class="col">	
			
				<label>Codigo</label>		
			   <div>
					<div>
						<input type="text" id="codbanco" name="codbanco" class="form-control col-2 col-xl-1 col-sm-8 text-right" readonly value="<?php echo $tipo["codbanco"]; ?>">
					</div>
				</div>
				
				<label>Nome</label>
				<div>
					<div>
						<input type="text" id="nome" name="nome" class="form-control col-6 col-xl-8 col-sm-8" value="<?php echo $tipo["nome"]; ?>">

					</div>
				</div>
				<br>
				<input type="submit" value="Alterar" class="btn btn-primary" >	
			</div>	
		</div>		
        </form>

</div>
<?php include("rodape.php"); ?>
