<?php

require("../include/config.php");

remover("clientes", "id={$_GET['id']}");

header('Location: clientes.php');