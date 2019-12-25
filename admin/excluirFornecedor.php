<?php

require("../include/config.php");

remover("fornecedor", "codfornec={$_GET['id']}");

header('Location: fornecedor.php');