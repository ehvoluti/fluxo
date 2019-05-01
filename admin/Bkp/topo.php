<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Fluxo Pessoal™</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <meta name="author" content="">

        <!-- Le styles -->
        <link href="../include/css/bootstrap.css" rel="stylesheet">
        <link href="../include/css/bootstrap-responsive.css" rel="stylesheet">
        <script src="../include/js/jquery.js"></script>
        <script src="../include/js/bootstrap.js"></script>
        <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <?php if(logado()): ?>
                <!-- Barra menus -->
                <div class="navbar">
                    <div class="navbar-inner">
                        <a class="brand" href="index.php">
                            ADMIN
                        </a>
                        <ul class="nav">
                            <?php
                            $paginas[] = array('url' => 'index.php', 'label' => 'Início');
                            //$paginas[] = array('url' => 'clientes.php', 'label' => 'Clientes');
                            $paginas[] = array('url' => 'categoria.php', 'label' => 'Catgorias');
                            $paginas[] = array('url' => 'funcionarios.php', 'label' => 'Funcionários');
                            $paginas[] = array('url' => 'tiposencomenda.php', 'label' => 'Tipos de Encomenda');
                            $paginas[] = array('url' => 'mensagens.php', 'label' => 'Mensagens');
                            ?>
                            <?php foreach($paginas as $link): ?>
                              <li<?php echo verifica_pagina($link) ? ' class="active"' : ''; ?>>
                                  <a href="<?php echo $link['url']; ?>"><?php echo $link['label']; ?></a>
                              </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="btn-group pull-right">
                            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                                <i class="icon-user"></i> <?php echo $_SESSION["usuario"]; ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="#"><i class="icon-wrench"></i> Opções</a></li>
                                <li class="divider"></li>
                                <li><a href="logout.php"><i class="icon-share"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div><!-- navbar -->
            <?php endif; ?>
