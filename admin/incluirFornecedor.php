<?php require_once("../include/config.php");?>
<?php
if($_POST) {
    if (inserir("fornecedor", $_POST)){
        header('Location: fornecedor.php');
    }
}

include("FornecedorForm.php");
?>

<?php include("rodape.php"); ?>

<script>
    var codfornec = document.getElementById('fornecedor')
    var page = 'ajax/geraid.php'
		$.ajax
			({
				type: 'GET',
				dataType: 'html',
				url: page,
				beforeSend: function() {
					$("#codfornec").html("Carrengado...");
				},
				data: {tabela: "fornecedor", campos: "codfornec"},
                success: function(msg) {
                    resp = msg.split(":")
                    //$("#teste").html(resp[1])
                    $("#codfornec").val(resp[0])
            }
			});
        
</script>