<?php require_once("../include/config.php");?>
<?php
if($_POST) {
    if (inserir("catlancto", $_POST)){
        header('Location: categoria.php');
    }
}

include("CategoriaForm.php");
?>

<?php include("rodape.php"); ?>
