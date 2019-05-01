<?php

require("../include/config.php");

remover("fale_conosco", "id={$_GET['id']}");

header('Location: mensagens.php');