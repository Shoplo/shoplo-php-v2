<?php

namespace Shoplo;

class Resource
{
    protected $client;
	protected $bucket = array();
    protected $prefix = '';

	public function __construct($client)
	{
        $this->client = $client;
	}

	protected function prepare_params($params)
	{
		$string = '';
		if (is_array($params)) {
			foreach ($params as $k => $v) if (!is_array($v)) $string .= $k . '=' . rawurlencode($v) . '&';
			$string = substr($string, 0, strlen($string) - 1);
		}
		return $string;
	}

    protected function prepare_result($result)
	{
		if (!is_array($result)) return array();

		if (isset($result['status'])) return $result;

		return $result;
	}

	protected function send($uri, $request = 'GET', $fields = array())
	{
        if ( 0 === strpos($uri, '/') ) $uri = substr($uri, 1);
        $uri = '/services/'.$uri;

        $method = $request;
        $body = [];

        if( $method == OAUTH_HTTP_METHOD_POST || $method == OAUTH_HTTP_METHOD_PUT )
        {
            $body = http_build_query($fields);
        }

        try
        {

            if( !$this->client instanceof \Oauth )
                throw new ShoploException("No authorisation");

            $this->client->fetch(SHOPLO_API_URL.$uri, $body, $method);
            $result = json_decode( $this->client->getLastResponse(), true);
        }
        catch( \Exception $e )
        {
            throw new AuthException($e->getMessage());
        }

        if ( isset($result['status']) && $result['status'] == 'err' )
        {
            if ( $result['error'] == '202' ) #Authorize error - need generate new access token
            {
                throw new AuthException($result['error_msg']);
            }
            throw new ShoploException($result['error_msg']);
        }
        return $result;
	}

	public function __destruct()
	{
		unset($this->client);
		unset($this->bucket);
	}
}
