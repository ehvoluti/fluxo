<?php require_once("../include/config.php");?>
<?php
if($_POST) {
    if (inserir("subcatlancto", $_POST)){
        header('Location: subcategoria.php');
    }
}

include("SubCategoriaForm.php");
?>

<?php include("rodape.php"); ?>

<script>
    var codsubcatlancto = document.getElementById('codsubcatlancto')
    var page = 'ajax/geraid.php'
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#codsubcatlancto").html("Carrengado...");
				},
				data: {tabela: "subcatlancto", campos: "codsubcatlancto"},
                success: function(msg) {
                    resp = msg.split(":")
                    //$("#teste").html(resp[1])
                    $("#codsubcatlancto").val(resp[0])
            }
			});
        
</script>