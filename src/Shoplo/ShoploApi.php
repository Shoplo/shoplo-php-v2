<?php

namespace Shoplo;

define('SHOPLO_API_URL','http://api.shoplo.com');
define('SHOPLO_REQUEST_TOKEN_URI', '/services/oauth/request_token');
define('SHOPLO_ACCESS_TOKEN_URI', '/services/oauth/access_token');
define('SHOPLO_AUTHORIZE_URL', SHOPLO_API_URL . '/services/oauth/authorize');
define('SHOPLO_IS_LOGGED_IN_PANEL_URL', SHOPLO_API_URL . '/services/admin/isloggedin');


class ShoploApi
{
    /**
     * @var String
     */
    private $api_key;

    /**
     * @var String
     */
    private $secret_key;

    /**
     * @var ShoploAuthStore
     */
    private $auth_store;

    /**
     * @var String
     */
    private $oauth_token;

    /**
     * @var String
     */
    private $oauth_token_secret;

    /**
     * @var Boolean
     */
    public $authorized = false;

    /**
     * @var Assets
     */
    public $assets;

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var Category
     */
    public $category;

    /**
     * @var Collection
     */
    public $collection;

    /**
     * @var Customer
     */
    public $customer;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var OrderStatus
     */
    public $order_status;

    /**
     * @var Product
     */
    public $product;

    /**
     * @var ProductImage
     */
    public $product_image;

    /**
     * @var ProductVariant
     */
    public $product_variant;

    /**
     * @var Shop
     */
    public $shop;

    /**
     * @var Theme
     */
    public $theme;

    /**
     * @var User
     */
    public $user;

    /**
     * @var Webhook
     */
    public $webhook;

    /**
     * @var Page
     */
    public $page;

    /**
     * @var Checkout
     */
    public $checkout;

    /**
     * @var Voucher
     */
    public $voucher;

    /**
     * @var Promotion
     */
    public $promotion;

    /**
     * @var ApplicationCharge
     */
    public $application_charge;

    /**
     * @var RecurringApplicationCharge
     */
    public $recurring_application_charge;

    public function __construct($config, $authStore=null, $disableSession=false)
    {
        if ( !$disableSession && !session_id() )
        {
            throw new ShoploException('Session not initialized');
        }
        if ( !isset($config['api_key']) || empty($config['api_key']) )
        {
            throw new ShoploException('Invalid Api Key');
        }
        elseif ( !isset($config['secret_key']) || empty($config['secret_key']) )
        {
            throw new ShoploException('Invalid Api Key');
        }
        elseif ( !isset($config['callback_url']) || empty($config['callback_url']) )
        {
            throw new ShoploException('Invalid Callback Url');
        }


        $this->api_key    = $config['api_key'];
        $this->secret_key = $config['secret_key'];

        $shopDomain = null;
        if( isset($_GET['shop_domain']) )
        {
            $shopDomain = addslashes($_GET['shop_domain']);
            $_SESSION['shop_domain'] = $shopDomain;
        }

        $this->callback_url = (false === strpos($config['callback_url'], 'http')) ? 'http://'.$config['callback_url'] : $config['callback_url'];

        $this->auth_store = AuthStore::getInstance($authStore);

        $this->authorize();


        $client = $this->getClient();
        $this->assets          = new Assets($client);
        $this->category        = new Category($client);
        $this->cart        	   = new Cart($client);
        $this->collection      = new Collection($client);
        $this->customer        = new Customer($client);
        $this->order           = new Order($client);
        $this->order_status    = new OrderStatus($client);
        $this->product         = new Product($client);
        $this->product_image   = new ProductImage($client);
        $this->product_variant = new ProductVariant($client);
        $this->vendor          = new Vendor($client);
        $this->shop            = new Shop($client);
        $this->webhook         = new Webhook($client);
        $this->theme           = new Theme($client);
        $this->page            = new Page($client);
        $this->shipping        = new Shipping($client);
        $this->checkout        = new Checkout($client);
        $this->voucher         = new Voucher($client);
        $this->promotion       = new Promotion($client);
        $this->user            = new User($client);
        $this->application_charge 	= new ApplicationCharge($client);
        $this->recurring_application_charge 	= new RecurringApplicationCharge($client);
    }


    public function authorize()
    {

        if ( $this->auth_store->authorize() )
        {
            $this->oauth_token        = $this->auth_store->getOAuthToken();
            $this->oauth_token_secret = $this->auth_store->getOAuthTokenSecret();
            $this->authorized         = true;

            return true;
        }

        if ( empty($_GET["oauth_token"]) )
        {
            $this->requestToken();
        }
        else
        {
            $this->accessToken();
        }

        $this->authorized = true;

        return true;
    }

    private function requestToken()
    {
        $client = $this->getClient();

        try
        {
            $response = $client->getRequestToken(SHOPLO_API_URL.SHOPLO_REQUEST_TOKEN_URI, CALLBACK_URL);
        }
        catch( \Exception $e )
        {
            throw new ShoploException($e->getMessage());
        }

        $client->setToken($response['oauth_token'], $response['oauth_token_secret']);

        $_SESSION['oauth_token_secret'] = $response['oauth_token_secret'];

        if( isset($_SESSION['shop_domain']) && $_SESSION['shop_domain'] )
        {
            $shopDomain = $_SESSION['shop_domain'];
            $callback_uri = $this->callback_url . '?consumer_key='.rawurlencode($this->api_key).'&shop_domain='.$shopDomain;

            unset($_SESSION['shop_domain']);
        }
        else
            $callback_uri = $this->callback_url . '?consumer_key='.rawurlencode($this->api_key);

        $uri = SHOPLO_AUTHORIZE_URL . '?oauth_token='.rawurlencode($response['oauth_token']).'&oauth_callback='.rawurlencode($callback_uri);

        header('Location: '.$uri);
        exit();
    }

    private function accessToken()
    {
        //  STEP 2:  Get an access token
        $client = $this->getClient($_GET['oauth_token'], $_SESSION['oauth_token_secret']);

        try
        {
            $response = $client->getAccessToken(SHOPLO_API_URL.SHOPLO_ACCESS_TOKEN_URI, null, $_GET['oauth_verifier']);
        }
        catch( \Exception $e )
        {
            throw new ShoploException($e->getMessage());
        }

        unset($_SESSION['oauth_token_secret']);

        $this->oauth_token = $response['oauth_token'];
        $this->oauth_token_secret = $response['oauth_token_secret'];

        $this->auth_store->setAuthorizeData($response['oauth_token'], $response['oauth_token_secret']);
    }

    public function getClient($token=null, $tokenSecret=null)
    {
        $token = !is_null($token) ? $token : ($this->oauth_token ? $this->oauth_token : '');
        $tokenSecret = !is_null($tokenSecret) ? $tokenSecret: ($this->oauth_token_secret ? $this->oauth_token_secret : '');

        $oauth = new \OAuth($this->api_key, $this->secret_key);
        if( $token )
            $oauth->setToken($token, $tokenSecret);

        return $oauth;
    }

    public function getOAuthToken()
    {
        return $this->oauth_token;
    }

    public function getOAuthTokenSecret()
    {
        return $this->oauth_token_secret;
    }

    public function __destruct()
    {
        unset($this->api_key);
        unset($this->secret_key);
        unset($this->oauth_token);
        unset($this->oauth_token_secret);
        unset($this->category);
        unset($this->cart);
        unset($this->collection);
        unset($this->customer);
        unset($this->order);
        unset($this->product);
        unset($this->product_image);
        unset($this->product_variant);
        unset($this->vendor);
        unset($this->shop);
        unset($this->theme);
        unset($this->user);
        unset($this->webhook);
        unset($this->application_charge);
        unset($this->recurring_application_charge);
    }


}