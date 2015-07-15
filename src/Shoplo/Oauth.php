<?php

namespace Shoplo;


class ShoploOauth
{
    /**
     * Return the signature base string.
     * Note that we can't use rawurlencode due to specified use of RFC3986.
     *
     * @return string
     */
    function signatureBaseString ($method, $requestUrl, $params)
    {
        $sig 	= array();
        $sig[]	= $method;
        $sig[]	= $requestUrl;
        $sig[]	= $params;

        return implode('&', array_map(array($this, 'urlencode'), $sig));
    }

    function signature ( $base_string, $consumer_secret, $token_secret )
    {
        $key = $this->urlencode($consumer_secret).'&'.$this->urlencode($token_secret);
        if (function_exists('hash_hmac'))
        {
            $signature = base64_encode(hash_hmac("sha1", $base_string, $key, true));
        }
        else
        {
            $blocksize    = 64;
            $hashfunc    = 'sha1';
            if (strlen($key) > $blocksize)
            {
                $key = pack('H*', $hashfunc($key));
            }
            $key    = str_pad($key,$blocksize,chr(0x00));
            $ipad    = str_repeat(chr(0x36),$blocksize);
            $opad    = str_repeat(chr(0x5c),$blocksize);
            $hmac     = pack(
                'H*',$hashfunc(
                    ($key^$opad).pack(
                        'H*',$hashfunc(
                            ($key^$ipad).$base_string
                        )
                    )
                )
            );
            $signature = base64_encode($hmac);
        }
        return $this->urlencode($signature);
    }

    /**
     * Encode a string according to the RFC3986
     *
     * @param string s
     * @return string
     */
    function urlencode ( $s )
    {
        if ($s === false)
        {
            return $s;
        }
        else
        {
            return str_replace('%7E', '~', rawurlencode($s));
        }
    }

    /**
     * Decode a string according to RFC3986.
     * Also correctly decodes RFC1738 urls.
     *
     * @param string s
     * @return string
     */
    function urldecode ( $s )
    {
        if ($s === false)
        {
            return $s;
        }
        else
        {
            return rawurldecode($s);
        }
    }

    /**
     * urltranscode - make sure that a value is encoded using RFC3986.
     * We use a basic urldecode() function so that any use of '+' as the
     * encoding of the space character is correctly handled.
     *
     * @param string s
     * @return string
     */
    function urltranscode ( $s )
    {
        if ($s === false)
        {
            return $s;
        }
        else
        {
            return $this->urlencode(rawurldecode($s));
            // return $this->urlencode(urldecode($s));
        }
    }
}