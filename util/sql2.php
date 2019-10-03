<?php
require_once("websac/require_file.php");
require_file("def/require_php.php");
?>
<head>
	<title>SQL 2.0</title>
	<?php require_file("def/require_head.php"); ?>
    <script type="text/javascript">
		$(document).bind("keydown", function(e){
			switch(e.keyCode){
				case 116:
					executar();
					return false;
					break;
			}
			return true;
		});


		function executar(){
			let txtareaquery = document.getElementById("query");
			let var_query = txtareaquery.value.substring(txtareaquery.selectionStart, txtareaquery.selectionEnd);
			if(var_query.length <= 0){
				var_query = txtareaquery.value;
			}
			$("#resultado").val("Carregando...");
			$.ajax({
				url: "pgadmin_executar.php",
				data: ({
					query: var_query
				}),
				success: function(html){
					
					$("#resultado").html(html);
				}
			});
		}
	</script>
	<style>
		#query{
			height: 200px;
			width: auto;
			border: solid 2px black;
			margin: 10px;
			background-color: #444;
			color: orange;
			font-size: 14px;
			font-family: monospace;
		}
		#resultado{
			height: fit-content;
			width: fit-content;
			border: solid 1px #ccc;
			margin: 10px;
			overflow: hidden;
		}
		#gridquery{
			overflow: scroll;
			width: 874px;
			height: 200px;
		}

		.sptexto{
			font-size: 10px;
			color: "333";
			padding-left: 20px;
		}
	</style>
</head>
<body>
    <div id="divScreen">
        <table id="mainForm" class="mainform">
            <tr>
                <td>
                    <ul id="tabscad" class="tabs">
						<li class="title" style="width:898px"><img style="position:relative;top:-2px" src="../img/repararean.png">&nbsp;<div style="left:40px;position:absolute;top:3px">SQL 2.0</div></li>
                    </ul>
                </td>
            </tr>

			<tr>
				<td style="vertical-align: top; width: 100%">
					<div id="divmain" class="tabpage">
						<table>
							<tr>
								<td style="vertical-align: top;">
									<!--<div contenteditable="true" id="query"></div>-->
									<textarea cols="104"  id="query"></textarea>
								</td>
							</tr>
							<tr>
								<td style="text-align: center">
									<button onclick="executar()">Executar (F5)</button>
								</td>
							</tr>
							<tr>
								<td style="vertical-align: top;">
									<div id="gridquery">
										<div class="gridborder" id="resultado">

										</div>
									</div>
								</td>
							</tr>

						</table>
					</div>
				</td>
			</tr>
		</table>
	</div>
</body>