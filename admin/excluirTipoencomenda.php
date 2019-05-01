<?php

require("../include/config.php");

remover("catlancto", "id={$_GET['id']}");

header('Location: categoria.php');