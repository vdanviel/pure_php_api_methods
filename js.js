//TOY BUTTON botão se ove assim que você coloca mouse em cima
$("#btn").hover((ev) => {

	//o X de maximo que o botão pode ir para a direita
 	let max_x = ($(window).width() / 2) - $(ev.target).width() - 90;

 	//o Y máximo que o botão pode ir para baixo
 	let max_y = (-$(window).height() / 3) - $(ev.target).height() + 90;

 	//o maximo que o X pode ir para esquerda
 	let min_x = (-$(window).height() / 2) - $(ev.target).width() + 90;

 	//o maximo que o X pode ir para cima
 	let min_y = $(window).height() / 2 - $(ev.target).height() - 90;

	//Math.pow(base, expoente) - eleva um numero (base) pelo (expoente)
	let random_positions = {
		"top": Math.random() * (max_y - min_y) + min_y,
		"left": Math.random() * (max_x - min_x) + min_x
	}

	$(ev.target).css({

		"position": "relative",
		"top": random_positions.top,
		"left": random_positions.left,

	});
});

//estikizando documento
$(document).ready(() => {

	//pegando todos os produtos pela api
	$.get("http://localhost:8000/products", {}).done((data) => {

		//adicionando produtos ao elemento select
		$.each(data, (i, line) => {

			$("#products").append("<option>" + line.id +"/"+ line.name + "</option>")

		})

	})

	$("html").css({
		"height": "100%"
	});

	$("body").css({
		"height": "100%",
	});

	$("div").css({
		"display": "flex",
		"justify-content": "center",
		"align-items": "center",
		"flex-direction": "column",
		"height": "100%"
	})

	//API que dá todos os produtos: ajax

})

//adiciona os dados no formulário conforme o produto selecionado
$("#products").change((ev) => {

	//ev.target.value - pega o valor do option selecionado
	const user_id = ev.target.value.split("/");

	$.ajax({
		"url": "http://localhost:8000/product/find?id=" + user_id[0].replace('#',""),
		"method": "GET",
		"dataType": "json",
		"success": function(ok){

      //tirando "$" cifrão do campo price
      ok.price = ok.price.replace("$", "");

			$("#form").html('');
			$('#form').append('<input type="hidden" name="id" value="'+ok.id+'">');
      $('#form').append('<label for="name">name');
			$('#form').append('<input id="name" type="text" value="'+ok.name+'" name="name">');
      $('#form').append('</label>');
      $('#form').append('<label for="price">price');
			$('#form').append('<input id="price" type="text" value="'+ok.price+'" name="price">');
      $('#form').append('</label>');
      $('#form').append('<label for="description">description');
			$('#form').append('<textarea id="description" rows="8" cols="80" name="description">'+ok.description+'</textarea>');
      $('#form').append('</label>');
      $('#form').append('<br>');
			$('#form').append('<button id="send">EDIT THIS PRODUCT</button>');

		},
		"error": function(err){

			console.error(err);

		}
	})

})

/*
	Neste exemplo, os eventos click são delegados ao elemento #form, que é um elemento pai dos botões adicionados dinamicamente. Assim, mesmo que os botões sejam adicionados dinamicamente, os eventos click serão acionados corretamente.
*/
$('#form').on('click', '#send', () => {

	const shiels = {
		id: $("#form input").eq(0).val(),
		name: $("#form input").eq(1).val(),
		price: $("#form input").eq(2).val(),
		description: $("#form").find("textarea").val()
	}

	//retirando o "#" da string do id
	shiels.id = shiels.id.replace("#","");

	$.ajax({
		url: "http://localhost:8000/product/edit",
		method: 'PUT',
    dataType: "json",
		data: {
			id: shiels.id,
			name: shiels.name,
			price: shiels.price,
			description: shiels.description
		},
		success: function(ok){

			console.log(ok);
      window.alert("product has been edited with success.");
      window.location.reload();

		},
		error: function(err){

			console.error(err.status + " / " + err.responseJSON);
      console.log(err);

		}
	})

});
