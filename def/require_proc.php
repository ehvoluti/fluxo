<?php

session_start();

//header("Content-Length: ".filesize($_SERVER["SCRIPT_FILENAME"]));

require_once("websac/require_file.php");
require_file("class/connection.class.php");
require_file("class/temporary.class.php");
require_file("class/log.class.php");
require_file("def/function.php");