<?php

require("../include/config.php");

remover("lancamento", "codlancto={$_GET['id']}");

header('Location: lancamento.php');