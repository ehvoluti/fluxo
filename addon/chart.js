$.fn.chart = function(settings){
	settings = $.extend({
		labels:[],
		values:{}
	},settings);

	// Verifica se precisa criar espaco para valores negativos
	var xaxispos = "bottom";
	for(var i in settings.values){
		for(var j in settings.values[i]){
			if(settings.values[i][j] < 0){
				xaxispos	= "center";
				break;
			}
		}
	}

	// Numero de linha horizontais e verticais na grade do fundo
	var numhlines = Math.floor($(this).height() / 15);
	var numvlines = Math.floor($(this).width() / 15);

	// Separa os valores das chaves e cria os tooltips
	var arr_keys = [];
	var arr_values = [];
	var arr_tooltips = [];
	var i = 0;
	for(var key in settings.values){
		arr_keys[i] = key;
		arr_values[i] = settings.values[key];
		arr_tooltips[i] = [];
		for(var j in settings.values[key]){
			arr_tooltips[i][j] = "<label style='font-size:10px'>" + settings.labels[j] + ": " + number_format(settings.values[key][j],4,",",".") + "</label>";
		}
		i++;
	}

	// Deixa alguns labels em branco
	if(settings.labels.length > 10){
		var i = 0;
		for(var index in settings.labels){
			if(i % Math.floor(settings.labels.length / 6) != 0){
				settings.labels[index] = "";
			}
			i++;
		}
	}

	$(this).each(function(){
		chart = new RGraph.Line($(this).attr("id"),arr_values[0],arr_values[1],arr_values[2],arr_values[3],arr_values[4],arr_values[5]);
		chart.Set("chart.background.barcolor1","white");
		chart.Set("chart.background.barcolor2","white");
		chart.Set("chart.background.grid",true);
		chart.Set("chart.background.grid.autofit",true);
		chart.Set("chart.background.grid.autofit.numhlines",numhlines);
		chart.Set("chart.background.grid.autofit.numvlines",numvlines);
		chart.Set("chart.colors",["#F00","#0F0","#00F","#FF0","#F0F","#0FF","#FF8000","#800080","#808000","008080"]);
		chart.Set("chart.gutter",30);
		chart.Set("chart.key",arr_keys);
		chart.Set("chart.key.position","gutter");
//		chart.Set("chart.key.position.y",0);
		chart.Set("chart.key.shadow",true);
		chart.Set("chart.key.shadow.offsetx",0);
		chart.Set("chart.key.shadow.offsety",0);
		chart.Set("chart.key.shadow.blur",10);
		chart.Set("chart.key.shadow.color","#DDD");
		chart.Set("chart.key.rounded",true);
		chart.Set("chart.labels",settings.labels);
		chart.Set("chart.linewidth",3);
		chart.Set("chart.scale.point",",");
		chart.Set("chart.shadow",true);
		chart.Set("chart.shadow.blur",3);
		chart.Set("chart.shadow.color","#999");
		chart.Set("chart.shadow.offsetx",0);
		chart.Set("chart.shadow.offsety",0);
		chart.Set('chart.tickmarks',chart_tickmark);
		chart.Set("chart.text.size",8);
		chart.Set("chart.text.angle",0);
//		chart.Set("chart.tooltips",arr_tooltips[0],arr_tooltips[1],arr_tooltips[2]);
		chart.Set("chart.tooltips.effect","snap");
		chart.Set("chart.variant","3d");
		chart.Set("chart.xticks",8);
		chart.Set("chart.xaxispos",xaxispos);

		chart.Draw();
	});

	return this;
}

function chart_tickmark(obj, data, value, index, x, y, color, prevX, prevY){
	value = number_format(value,2,",",".");

	var chart = $("#" + $(obj).attr("id"));
    if($(chart).length > 0){
        var div = document.createElement("div");
        document.body.appendChild(div);
        $(div).attr({
            "chart_id":$(chart).attr("id"),
            "chart_value":value
        }).css({
            "left":$(chart).offset().left + x - 4,
            "height":"6px",
            "position":"absolute",
            "top":$(chart).offset().top + y - 4,
            "width":"6px"
        }).css3({
            "border-radius":"5px"
        }).bind("mouseenter",function(){
            if($("#" + $(this).attr("chart_id")).is(":visible")){
                $(this).css({
                    "background-color":"#FFF",
                    "border":"1px solid #333"
                });
                var div = document.createElement("div");
                document.body.appendChild(div);
                $(div).attr({
                    "chart_tickmark":true
                }).css({
                    "background-color":"RGBA(255,255,255,0.8)",
                    "border":"1px solid #777",
                    "color":"#222",
                    "font-size":"10px",
                    "padding":"2px 5px",
                    "position":"absolute"
                }).html($(this).attr("chart_value")).css({
                    "left":$(this).offset().left - $(div).width() / 2,
                    "top":$(this).offset().top - $(div).height() - 10
                }).css3({
                    "border-radius":"3px",
                    "box-shadow":"0px 0px 5px #999"
                });
            }
        }).bind("mouseout",function(){
            $(this).css({
                "background":"none",
                "border":"none"
            });
            $("[chart_tickmark]").remove();
        });
    }
}