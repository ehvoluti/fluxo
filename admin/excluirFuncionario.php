<?php

require("../include/config.php");

remover("funcionarios", "id={$_GET['id']}");

header('Location: funcionarios.php');