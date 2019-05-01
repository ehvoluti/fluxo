<!DOCTYPE html>
<html>
	<head>
		<title>Fluxo Pessoal™</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta charset="utf-8">
		<!-- Bootstrap -->
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="css/all.css" rel="stylesheet"> <!--load all styles -->
		
			<!-- Ainda não esta em uso
			<link href="css/style.css" rel="stylesheet">
			-->
	</head>
    <body>
        <div class="container">
            <?php if(logado()): ?>
			<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
			  <a class="navbar-brand" href="#">ADMIN</a>
			  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			  </button>

			  <div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav mr-auto">
				  <!-- Cadastro -->
				  <li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					  Cadastro
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					  <a class="dropdown-item" href="categoria.php">Categoria</a>
					  <a class="dropdown-item" href="subcategoria.php">SubCategoria</a>
					  <div class="dropdown-divider"></div>
					  <a class="dropdown-item" href="#">Fornecedor</a>
					  <a class="dropdown-item" href="bancos.php">Banco</a>
					</div>
				  </li>
					<!-- Controle -->
				  <li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					  Fluxo de Caixa
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					  <a class="dropdown-item" href="controle2.php">Lançamento</a>
					  <a class="dropdown-item" href="#">Liquidação</a>
					</div>  
				  </li>
					<!-- Relatorio -->
				  <li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					  Relatórios
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
					  <a class="dropdown-item" href="#">Extrato</a>
					  <a class="dropdown-item" href="#">Fluxo de Caixa</a>
					  <a class="dropdown-item" href="#">Categoria Anual</a>
					</div>
				  </li>
				</ul>
			  </div>
			</nav>
			
			<!-- jQuery (necessario para os plugins Javascript Bootstrap) -->
				<footer>
					<script src="js/jquery.min.js"></script>
					<script src="js/bootstrap.bundle.min.js"></script>
				</footer>	
		</div>
	 <?php endif; ?>	
</body>		
</html>