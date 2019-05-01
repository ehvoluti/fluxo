<?php

require("../include/config.php");

remover("banco", "codbanco={$_GET['id']}");

header('Location: bancos.php');