<?php

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

//vendo se a api está sendo chamada corretamente (por "/products")
preg_match('/(product)/', $uri, $product);

if(empty($product)){
    die();
}

switch ($method) {

    #GET
    case 'GET':

        if (isset($_GET['id'])) {

          //product/find/
          if ($uri === "/product/find?id=" . $_GET['id']) {

            /*
            By using header('Content-type: application/json');
            you are indicating that the response being sent back to the client will be in JSON format.
            */
            header('Content-type: application/json');
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET");

            //opening operation to access model.csv
            $find_instance = fopen('model.csv', 'r');

            /*
                If you want to retrieve all the lines from the CSV file,
                you need to call fgetcsv() in a loop until it returns false,
                indicating the end of the file.
            */

            //variavel que identifica se o registro requisitado pelo request existe
            $verify = false;

            //$line == the register row of the .csv
            while (($line = fgetcsv($find_instance,null,',',"'")) !== false) {

              //erasing the "#" on the id retrieving only the id number
              $id = ltrim($line[0], '#');

              //the model id becomes into int
              $id = intval($id);

              //if the .csv id is equal to the url id the register will be returned
              if ($id == $_GET['id']) {

                //oragnizing the array response
                $data = [
                    'id' => $line[0],
                    'name' => $line[1],
                    'price' => $line[2],
                    'description' => $line[3]
                ];

                http_response_code(200);

                //registro existe
                $verify = true;

                //returning the organized array reponse
                echo json_encode($data);

              }

          }

          //registro não existe
          if ($verify == false) {

            http_response_code(400);

            echo "this register do not exist on our database.";

          }

          //closing operation
          fclose($find_instance);

        }

      }

        //LISTA TODOS PRODUTOS
        if ($uri == "/products" || $uri == "/products/") {

            header("Content-type: application/json");
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET");

            http_response_code(200);

            //all of the .csv data
            $array_data = [];

            $file = fopen("model.csv", "r");

            while (($line = fgetcsv($file,null, ",", "'")) !== false) {
                array_push($array_data, $line);
            }

            $data = [];

            foreach ($array_data as $line) {

                array_push($data, [

                    "id" => $line[0],
                    "name" => $line[1],
                    "price" => $line[2],
                    "description" => $line[3]

                ]);

            }

            echo json_encode($data);
        }

    break;

    #POST
    case 'POST':

        #POST

        // /product/register/

        if ($uri == "/product/register/" || $uri == "/product/register") {

            header('Content-type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');

            http_response_code(201);

            //dados do POST
            $uri_data = [
                "name" => $_POST['name'],
                "price" => $_POST['price'],
                "description" => $_POST['description']
            ];

            //validando os dados
            $validate = credentials_validation($uri_data, ['name', 'price', 'description']);

            if ($validate !== true) {

                echo $validate;
                die();

            }

            //recuperar ultima linha do .csv
            $retrieve = fopen('model.csv', 'r');

            //o array dos registros
            $retrieve_data = array();

            while (($line = fgetcsv($retrieve,null,",","'")) !== false) {

                //adicionando cada linha no array $retrieve_data
                array_push($retrieve_data,$line);

            }

            fclose($retrieve);

            //o ultimo registro do .csv
            $last_retrieve = $retrieve_data[count($retrieve_data) - 1];

            //encontrando o ultimo id pelo pelo "#"
            $last_id = $last_retrieve[0];

            //apagando o "#"
            $last_id = ltrim($last_id, "#");

            //adicionando um a mais no id
            $last_id = intval($last_id) + 1;

            //transformando em stirng
            $last_id = strval($last_id);

            #ultimo id do .csv (adicionando "#" no array)
            $last_id = "#" . $last_id;
            $name = urldecode($uri_data["name"]);
            $price = "$" . urldecode($uri_data["price"]);
            $desc = urldecode($uri_data["description"]);

            //abrindo inatacnia para esvrever
            $insert = fopen("model.csv", "w");

            //adicionando o array do novo registro ao o array dos registros totais
            array_push($retrieve_data, [$last_id, $name, $price, $desc]);

            //variable where the formated array willbe
            $data = [];

            foreach ($retrieve_data as $fields) {

                //adicionando novo .csv ao arquivo
                fputcsv($insert, $fields,",","'");

                //formatando o array
                $data[] = [
                    'id' => $fields[0],
                    'name' => $fields[1],
                    'price' => $fields[2],
                    'description' => $fields[3]
                ];

            }

            fclose($insert);

            //organizando o csv
            sort_model();

            echo json_encode($data);
        }

    break;

    #PUT
    case 'PUT':

        //caso uri == /product/edit
        if ($uri == "/product/edit" || $uri == "/product/edit/") {

            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: PUT, OPTIONS');

            //dados raw entregados pelo request
            parse_str(file_get_contents("php://input"), $shields);

            //verifica a intreguidade dos dadso recebidos
            $validate = credentials_validation($shields, ["id", "name", "price", "description"]);

            if($validate !== true){

                echo $validate;
                die();

            }

            //construindo novo array, sem a presença do antigo registro a ser alterado
            $add_instance = fopen("model.csv", "r");

            //esse array vai ter todos os registros, menos o antigo registro que o request quer atualizar (para a substuição)
            $new_array = [];

            //construindo o novo array
            while(($line = fgetcsv($add_instance,null,",","'")) !== false){

                //tranformando o o id do csv em int
                if (isset($line[0])) {

                    $id_model = str_replace("#", "", $line[0]);

                    $id_model = intval($id_model);
                }

                //se o id do model for diferente do id do request, o registo entra nesse array
                if ($id_model != $shields['id']) {

                    $new_array[] = $line;

                }

            }

            fclose($add_instance);

            $verify_instance = fopen("model.csv", "r+");

            //capturando product que vai ser modificado... se ele exitir $verify == true se não $verify == false
            $verify = false;

            while(($line = fgetcsv($verify_instance,null,",","'")) !== false){

                //tranformando o o id do csv em int
                if (isset($line[0])) {

                    $id_model = str_replace("#", "", $line[0]);

                    $id_model = intval($id_model);

                }

                //se o id do request for o mesmo analizado do model...
                if ($id_model == $shields['id']) {

                    $verify = true;

                }

            }

            fclose($verify_instance);

            //aqui pegaremos o array modificado e vamos juntar com o registro alterado do request..
            $edit_instance = fopen("model.csv", "w");

            if ($verify == true) {

                //antes de adicionar a linha modificada vamos formatar o array do request..
                $new_line = [
                    "#" . strval($shields['id']), $shields['name'], "$" . strval($shields['price']), $shields['description']
                ];

                array_push($new_array, $new_line);

                //escreva os valores atualizados no arquivo..
                foreach ($new_array as $row) {

                    fputcsv($edit_instance,$row, ",", "'");

                }

                http_response_code(200);

                //organizando o csv..
                sort_model();

                echo json_encode(true);

                fclose($edit_instance);

                exit;

            }else {

                echo json_decode("sim");

            }

            http_response_code(404);

            echo json_encode("a");

            exit;

        }

    break;

    #PATCH
    case 'PATCH':

        if ($uri == "/product/edit/name" || $uri == "/product/edit/name/") {

          header('Access-Control-Allow-Origin: *');
          header('Access-Control-Allow-Methods: PUT, OPTIONS');

          //dados raw entregados pelo request
          parse_str(file_get_contents("php://input"), $shields);

          //verifica a intreguidade dos dadso recebidos
          $validate = credentials_validation($shields, ["id", "name"]);

          if($validate !== true){
              echo $validate;
              die();
          }

          //capturando os dados do csv
          $csv_data_instance = fopen("model.csv", "r");

          $csv_array = [];

          while (($line = fgetcsv($csv_data_instance, null, ",", "'")) !== false) {

            $csv_array[] = $line;

          }

          fclose($csv_data_instance);

          //mudando o registro dentro do array

          //variavel pra confirmar se o registro do solocitado no request existe
          $verify = false;

          foreach ($csv_array as &$row) {

            //transformando id do model em int sem o "#"
            if (isset($row[0])) {

              $id_model = str_replace("#", "", $row[0]);

              $id_model = intval($id_model);

            }

            if ($shields['id'] == $id_model) {

              //registro existe
              $verify = true;

              //mudou o dado requisitado no array
              $row[1] = $shields['name'];

            }

          }

          //modificando o arquivo model original com o dado editado..
          $edit_instance = fopen("model.csv", "w");

          //verificando se o registro realmente existe..
          if ($verify == true) {

            foreach ($csv_array as &$row) {

              //modificando o csv
              fputcsv($edit_instance, $row, ",", "'");

            }

          }else{

            //registro não existe..
            echo json_encode(false);
            die();

          }

          fclose($edit_instance);

          //sucesso na alteração do dado que o request pediu
          http_response_code(200);

          //organizando o csv..
          sort_model();

          echo json_encode(true);

        }

        if ($uri == "/product/edit/price" || $uri == "/product/edit/price/") {

          header('Access-Control-Allow-Origin: *');
          header('Access-Control-Allow-Methods: PUT, OPTIONS');

          //dados raw entregados pelo request
          parse_str(file_get_contents("php://input"), $shields);

          //verifica a intreguidade dos dadso recebidos
          $validate = credentials_validation($shields, ["id", "price"]);

          if($validate !== true){
              echo $validate;
              die();
          }

          //capturando os dados do csv
          $csv_data_instance = fopen("model.csv", "r");

          $csv_array = [];

          while (($line = fgetcsv($csv_data_instance, null, ",", "'")) !== false) {

            $csv_array[] = $line;

          }

          fclose($csv_data_instance);

          //mudando o registro dentro do array

          //variavel pra confirmar se o registro do solocitado no request existe
          $verify = false;

          foreach ($csv_array as &$row) {

            //transformando id do model em int sem o "#"
            if (isset($row[0])) {

              $id_model = str_replace("#", "", $row[0]);

              $id_model = intval($id_model);

            }

            if ($shields['id'] == $id_model) {

              //registro existe
              $verify = true;

              //mudou o dado requisitado no array
              $row[2] = "$" . strval($shields['price']);

            }

          }

          //modificando o arquivo model original com o dado editado..
          $edit_instance = fopen("model.csv", "w");

          //verificando se o registro realmente existe..
          if ($verify == true) {

            foreach ($csv_array as &$row) {

              //modificando o csv
              fputcsv($edit_instance, $row, ",", "'");

            }

          }else{

            //registro não existe..
            echo json_encode(false);
            die();

          }

          fclose($edit_instance);

          //sucesso na alteração do dado que o request pediu
          http_response_code(200);

          //organizando o csv..
          sort_model();

          echo json_encode(true);

        }

        if ($uri == "/product/edit/description" || $uri == "/product/edit/description/") {

          header('Access-Control-Allow-Origin: *');
          header('Access-Control-Allow-Methods: PUT, OPTIONS');

          //dados raw entregados pelo request
          parse_str(file_get_contents("php://input"), $shields);

          //verifica a intreguidade dos dadso recebidos
          $validate = credentials_validation($shields, ["id", "description"]);

          if($validate !== true){
              echo $validate;
              die();
          }

          //capturando os dados do csv
          $csv_data_instance = fopen("model.csv", "r");

          $csv_array = [];

          while (($line = fgetcsv($csv_data_instance, null, ",", "'")) !== false) {

            $csv_array[] = $line;

          }

          fclose($csv_data_instance);

          //mudando o registro dentro do array

          //variavel pra confirmar se o registro do solocitado no request existe
          $verify = false;

          foreach ($csv_array as &$row) {

            //transformando id do model em int sem o "#"
            if (isset($row[0])) {

              $id_model = str_replace("#", "", $row[0]);

              $id_model = intval($id_model);

            }

            if ($shields['id'] == $id_model) {

              //registro existe
              $verify = true;

              //mudou o dado requisitado no array
              $row[3] = $shields['description'];

            }

          }

          //modificando o arquivo model original com o dado editado..
          $edit_instance = fopen("model.csv", "w");

          //verificando se o registro realmente existe..
          if ($verify == true) {

            foreach ($csv_array as &$row) {

              //modificando o csv
              fputcsv($edit_instance, $row, ",", "'");

            }

          }else{

            //registro não existe..
            echo json_encode(false);
            die();

          }

          fclose($edit_instance);

          //sucesso na alteração do dado que o request pediu
          http_response_code(200);

          //organizando o csv..
          sort_model();

          echo json_encode(true);

        }

    break;

    #DELETE
    case 'DELETE':

        if ($uri == '/product/delete' || $uri == '/product/delete/') {

          header('Access-Control-Allow-Origin: *');
          header('Access-Control-Allow-Methods: DELETE');

          //dados raw entregados pelo request
          parse_str(file_get_contents("php://input"), $shields);

          //verificação dos dados do request..
          $validate = credentials_validation($shields, ['id']);

          if ($validate !== true) {

            echo $validate;
            die();

          }

          //criando um array sem o registro do id do request..

          $new_array_instance = fopen("model.csv", "r");

          //o array com o registro do request ausente..
          $new_array = [];

          //construindo o novo array
          while(($line = fgetcsv($new_array_instance,null,",","'")) !== false){

              //tranformando o o id do csv em int
              if (isset($line[0])) {

                  $id_model = str_replace("#", "", $line[0]);

                  $id_model = intval($id_model);

              }

              //se o id do model for diferente do id do request, o registo entra nesse array
              if ($id_model != $shields['id']) {

                  $new_array[] = $line;

              }

          }

          fclose($new_array_instance);

          //refazendo o csv com o registro do request excluido

          $new_model_instance = fopen("model.csv", "w");

          foreach ($new_array as $i => $row) {

            //reeinscrevendo csv
            fputcsv($new_model_instance,$row, ",", "'");

          }

          fclose($new_model_instance);

          //organizando o CSV
          sort_model();

          http_response_code(200);

          echo json_encode(true);

        }

    break;

    #OPTIONS - usado antes de certas solitações por segurança.
    case 'OPTIONS':

        // Configurar os cabeçalhos para permitir a solicitação OPTIONS
      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Methods: PUT, PATCH, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type');
      header('Access-Control-Max-Age: 86400');
      header('Content-Length: 0');
      header('Content-Type: text/plain');

    break;

    default:
        echo "The used method is incorrect.";
        http_response_code(400);
        die();
    break;
}

###########################################################################################################################################

#FUNCTIONS

//valida as credenciais enviadas no request
function credentials_validation($array, $array_keys) : string | bool
{

    //se os campos não existirem
    foreach ($array_keys as $array_key) {

      if (!isset($array[$array_key]) ) {

          http_response_code(400);
          $string_array_keys = implode(', ', $array_keys);
          return json_encode("required-shields: " . $string_array_keys);

      }

    }

    //se não houver dados: 400
    if (empty($array)) {

      http_response_code(400);
      $string_array_keys = implode(', ', $array_keys);
      return json_encode("(no array shields) required-shields: " . $string_array_keys);

    }

    foreach ($array_keys as $array_key) {

        //se os campos estiverem vazio:400
        if (empty($array[$array_key])) {

            http_response_code(400);
            $string_array_keys = implode(', ', $array_keys);
            return json_encode("(shiels are empty) required-shields: " . $string_array_keys);

        }

    }

    //passou pelas verificações
    return true;
}

//organiza os registros do csv em ordem crescente
function sort_model() : bool
{

    $original_instance = fopen("model.csv", "r");

    //pegando o array do model para organiza-lo posteriormente
    $formated_array = [];

    while(($line = fgetcsv($original_instance, null, ',', "'")) !== false){

        $formated_array[] = $line;

    }

    fclose($original_instance);

    //formatando o model

    // Função de comparação para ordenar com base nos IDs
    usort($formated_array, function($a, $b) {
        $idA = intval(ltrim($a[0], '#')); // Extrai o ID de cada registro
        $idB = intval(ltrim($b[0], '#'));
        return $idA - $idB; // Compara os IDs para determinar a ordem
    });


    $formated_instance = fopen("model.csv", "w");

    //reincrevendo o csv com as informações organizadas
    foreach ($formated_array as $row) {

        fputcsv($formated_instance, $row, ",", "'");

    }

    return true;

}
