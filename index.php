<?php

require 'include/Slim-2.x/Slim/Slim.php';
require_once "include/config.php";

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// GET route
$app->get(
    '/',
    function () {

        require_once("view/index.php");
        
    }
);

$app->get(
    '/home',
    function () {
        
        require_once("view/index.php");
        
    }
);

$app->get(
    '/videos',
    function () {
        
        require_once("view/videos.php");
        
    }
);

$app->get(
    '/shop',
    function () {
        
        require_once("view/shop.php");
        
    }
);

$app->get(
    '/produtos',
    function () {

    //SELECT * FROM tb_produtos WHERE preco_promorcional > 0 ORDER BY preco_promorcional DESC LIMIT 3;

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("SELECT * FROM tb_produtos WHERE preco_promorcional > 0 ORDER BY preco_promorcional DESC LIMIT 3;"); 

    foreach ($result as &$produto) {
        $preco = $produto["preco"];
        $centavos = explode(".", $preco);
        $produto["preco"] = number_format($preco, 0, ",", ".");
        $produto["centavos"] = end($centavos);
        $produto["parcelas"] = 10;
        $produto["parcela"] = number_format($preco / $produto["parcelas"], 2, ",", ".");
        $produto["total"] = number_format($preco, 2, ",", ".");

    }   

    echo json_encode($result);

    $sql->disconnect();

    }
);

$app->get(
    '/produtos-mais-buscados',
    function () {

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("
        SELECT 
        tb_produtos.id_prod,
        tb_produtos.nome_prod_curto,
        tb_produtos.nome_prod_longo,
        tb_produtos.codigo_interno,
        tb_produtos.id_cat,
        tb_produtos.preco,
        tb_produtos.peso,
        tb_produtos.largura_centimetro,
        tb_produtos.altura_centimetro,
        tb_produtos.quantidade_estoque,
        tb_produtos.preco_promorcional,
        tb_produtos.foto_principal,
        tb_produtos.visivel,
        cast(avg(review) as dec(10,2)) as media, 
        count(id_prod) as total_reviews
        FROM tb_produtos 
        INNER JOIN tb_reviews USING(id_prod) 
        GROUP BY 
        tb_produtos.id_prod,
        tb_produtos.nome_prod_curto,
        tb_produtos.nome_prod_longo,
        tb_produtos.codigo_interno,
        tb_produtos.id_cat,
        tb_produtos.preco,
        tb_produtos.peso,
        tb_produtos.largura_centimetro,
        tb_produtos.altura_centimetro,
        tb_produtos.quantidade_estoque,
        tb_produtos.preco_promorcional,
        tb_produtos.foto_principal,
        tb_produtos.visivel
        LIMIT 4;
    "); 

    foreach ($result as &$produto) {
        $preco = $produto["preco"];
        $centavos = explode(".", $preco);
        $produto["preco"] = number_format($preco, 0, ",", ".");
        $produto["centavos"] = end($centavos);
        $produto["parcelas"] = 10;
        $produto["parcela"] = number_format($preco / $produto["parcelas"], 2, ",", ".");
        $produto["total"] = number_format($preco, 2, ",", ".");

    }   

    echo json_encode($result);

    $sql->disconnect();

    }
);

$app->get(
    '/produto-:id_prod',
    function ($id_prod) {
        
        $sql = new Sql();
    
        $sql->connect();
        
        $produtos = $sql->select("SELECT * FROM tb_produtos WHERE id_prod = :ID", array(':ID' => $id_prod));

        $produto = $produtos[0];

        $preco = $produto["preco"];
        $centavos = explode(".", $preco);
        $produto["preco"] = number_format($preco, 0, ",", ".");
        $produto["centavos"] = end($centavos);
        $produto["parcelas"] = 10;
        $produto["parcela"] = number_format($preco / $produto["parcelas"], 2, ",", ".");
        $produto["total"] = number_format($preco, 2, ",", ".");

        $sql->disconnect();

        //echo json_encode($produto);

        require_once "view/shop-produto.php";

        
    }
);


$app->get(
    '/cart',
    function () {
        
        require_once("view/cart.php");
        
    }
);

$app->get('/carrinho-dados', function(){

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $carrinho['total_car'] = number_format((float)$carrinho['total_car'], 2, ',', '.');
    $carrinho['subtotal_car'] = number_format((float)$carrinho['subtotal_car'], 2, ',', '.');
    $carrinho['frete_car'] = number_format((float)$carrinho['frete_car'], 2, ',', '.');

    $carrinho['produtos'] = $result = $sql->select("CALL sp_carrinhosprodutos_list('".$carrinho['id_car']."')");

    echo json_encode($carrinho);

    $sql->disconnect();
});

$app->get('/carrinhoAdd-:id_prod', function($id_prod){

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $sql->select("CALL sp_carrinhosprodutos_add(".$carrinho['id_car']. ", ".$id_prod.")");

    $sql->disconnect();

    header("Location: cart");

    exit;

});

$app->delete("/carrinhoRemoveAll-:id_prod", function($id_prod) {

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $sql->select("CALL sp_carrinhosprodutostodos_rem(".$carrinho['id_car']. ", ".$id_prod.")");


    echo json_encode(array(

        "Success"=>true

    ));

    $sql->disconnect();
});

$app->post("/carrinho-produto", function() {

    $data = json_decode(file_get_contents("php://input"), true);

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $sql->select("CALL sp_carrinhosprodutos_add(".$carrinho['id_car']. ", ".$data['id_prod'].")");


    echo json_encode(array(

        "Success"=>true

    ));

    $sql->disconnect();


});

$app->delete("/carrinho-produto", function() {

    $data = json_decode(file_get_contents("php://input"), true);

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $sql->select("CALL sp_carrinhosprodutos_rem(".$carrinho['id_car']. ", ".$data['id_prod'].")");


    echo json_encode(array(

        "Success"=>true

    ));

    $sql->disconnect();


});

$app->get("/calcular-frete-:cep", function($cep) {

    require_once "include/php-calcular-frete-correios-master/Frete.php";

    $sql = new Sql();
    
    $sql->connect();

    $result = $sql->select("CALL sp_carrinhos_get('".session_id()."')");

    $carrinho = $result[0];

    $produtos = $sql->select("CALL sp_carrinhosprodutosfrete_list(".$carrinho['id_car'].")");

    $peso = 0;
    $comprimento = 0;
    $altura = 0; 
    $largura = 0;
    $valor = 0;

    foreach ($produtos as $produto) {
        $peso =+ $produto['peso'];
        $comprimento =+ $produto['comprimento'];
        $altura =+ $produto['altura'];
        $largura =+ $produto['largura'];
        $valor =+ $produto['preco'];
    }

    $cep = trim(str_replace('-', '', $cep));

    $frete = new Frete(
        $cepDeOrigem = '01418100', 
        $cepDeDestino = trim(str_replace('-', '', $cep)), 
        $peso, 
        $comprimento,
        $altura,
        $largura,
        $valor
    );

    $sql->select("
        UPDATE tb_carrinhos 
        SET 
            cep_car = '".$cep."', 
            frete_car = ".$frete->getValor().",
            prazo_car = ".$frete->getPrazoEntrega()."
        WHERE id_car = ".$carrinho['id_car']
    );

    echo json_encode(array(
        'valor_frete'=>$frete->getValor()
    ));

    $sql->disconnect();

}); 

$app->run();
