<?php
/**
 * Created by JetBrains PhpStorm.
 * User: grzegorzlech
 * Date: 12-08-06
 * Time: 14:00
 * To change this template use File | Settings | File Templates.
 */

require_once __DIR__ . '/vendor/autoload.php';

//ini_set('display_errors','TRUE');

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
        if( $_GET['oauth_token'] && $_GET['oauth_verifier'] && $_SESSION['oauth_token_secret'] )
        {
            $result = $shoploApi->accessToken($_GET['oauth_token'], $_SESSION['oauth_token_secret'], $_GET['oauth_verifier']);

            //save them and pass every time You want pass api request
            $token = $result['oauth_token'];
            $tokenSecret = $result['oauth_token_secret'];

            $shoploApi->initClient($token, $tokenSecret);
        }
        else
            $shoploApi->requestToken();
    }


    try
    {
        # add product
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

//        $product = $shoploApi->product->modify(6, ['name'=>'test api v2']);
//        print_r($product);exit;
        # retrieve all products
        $data = $shoploApi->product->retrieve();
        # count all products
//        $data = $shoploApi->product->count();
        #retrieve all categories
//        $data = $shoploApi->category->retrieve();
        #retrieve shop data
//        $data = $shoploApi->shop->retrieve();
        print_r($data);exit;
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
    foreach ( $data as $d )
    {
        echo "<tr>
                <td>".$d['id']."</td>
                <td>".$d['name']."</td>
                <td>".$d['url']."</td>
                <td>".$d['description']."</td>
                <td>".$d['delivery_need']."</td>
              </tr>";
    }
    echo "</table>";
}
catch ( Shoplo\ShoploException $e )
{
    echo 'Throw Shoplo Exception: '.$e->getMessage();
    exit();
}