<?php

require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors','TRUE');
error_reporting(E_ALL);

define('SECRET_KEY','xGTta0zGZGDSJOCBeTMuOR4DnUMXCpd6');
define('CONSUMER_KEY', 'YJIM6mdkfv2qfTWxRInC8ltd9HCkNhNs');
define('CALLBACK_URL', 'http://localhost/shoplo-php-v2/example.php');

session_start();

try
{
    $config = array(
        'api_key'      =>  CONSUMER_KEY,
        'secret_key'   =>  SECRET_KEY,
        'callback_url' =>  CALLBACK_URL,
    );
    $shoploApi = new Shoplo\ShoploApi($config);
    if( !$shoploApi->authorized )
    {

        if( isset($_GET['oauth_token']) && $_GET['oauth_token'] && isset($_GET['oauth_verifier']) && $_GET['oauth_verifier'] && isset($_SESSION['oauth_token_secret']) && $_SESSION['oauth_token_secret'] )
        {
            $result = $shoploApi->accessToken($_GET['oauth_token'], $_SESSION['oauth_token_secret'], $_GET['oauth_verifier']);

            $token = $result['oauth_token'];
            $tokenSecret = $result['oauth_token_secret'];

            $shoploApi->initClient($token, $tokenSecret);
        }
        else
        {
            $response = $shoploApi->requestToken();
            header('Location: '.$response['login_url']);
            exit();
        }
    }

    try
    {
        $productInfo = array(
            'title'             =>  'Penne Rigate makaron pióra 500g',
            'description'       =>  'Najwyższej jakości makaron wyprodukowany w 100% z semoliny z pszenicy durum. Najlepiej smakuje z sosem pomidorowym z boczkiem, mięsem lub rybami.',
            'short_description' =>  '',
            'require_shipping'  =>  1,
            'availability'      =>  1,
            'visibility'        =>  1,
            'sku'               =>  'PR-500-P',
            'weight'            =>  50,
            'width'             =>  0,
            'height'            =>  0,
            'depth'             =>  0,
            'diameter'          =>  0,
            'buy_if_empty'      =>  0,
            'quantity'          =>  1,
            'price'             =>  619,
            'price_regular'     =>  619,
            'tax'               =>  23,
            'images'            =>  array(
                array(
                    'src'       => 'http://lorempixel.com/640/480/food/',
                    'title'     => '',
                    'img_main'  => true
                )
            ),
            'vendor'            =>  'Barilla',
            'category'          =>  array( 'Makarony', 'Delikatesy' ),
            'collection'        =>  array('Zdrowa żywność'),
            'tags'              =>  'penne,makaron,zdrowy'
        );

        $data = $shoploApi->product->retrieve();

    }
    catch ( \Shoplo\AuthException $e )
    {
        unset($_SESSION['oauth_token']);
        header('Location: '.CALLBACK_URL);
        exit();
    }


    echo "<table>";
    echo "<tr>
                <td>id</td>
                <td>name</td>
                <td>url</td>
                <td>description</td>
                <td>delivery need</td>
              </tr>";

    if( isset($data['products']) ){

        foreach ( $data['products'] as $d ) {

            if( !(isset($d['id']) && isset($d['name']) && isset($d['url']) && isset($d['description']) && isset($d['delivery_need'])) )
                continue;

            echo "<tr>
                <td>".$d['id']."</td>
                <td>".$d['name']."</td>
                <td>".$d['url']."</td>
                <td>".$d['description']."</td>
                <td>".$d['delivery_need']."</td>
              </tr>";
        }
    }

    echo "</table>";
}
catch ( Shoplo\ShoploException $e )
{
    echo 'Throw Shoplo Exception: '.$e->getMessage();
    exit();
}