<?php

require("../include/config.php");

remover("controle", "idcontrole={$_GET['id']}");

header('Location: controle2.php');