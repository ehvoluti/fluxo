<?php

require("../include/config.php");

remover("catlancto", "codcatlancto={$_GET['id']}");

header('Location: categoria.php');