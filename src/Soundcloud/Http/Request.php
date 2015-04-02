<?php

namespace Njasm\Soundcloud\Http;

use Njasm\Soundcloud\Http\Url\UrlBuilder;

/**
 * SoundCloud API wrapper in PHP
 *
 * @author      Nelson J Morais <njmorais@gmail.com>
 * @copyright   2014 Nelson J Morais <njmorais@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        http://github.com/njasm/soundcloud
 * @package     Njasm\Soundcloud
 * @since       3.0.0
 */

class Request implements RequestInterface
{
    /** @var string */
    private $verb;
    /** @var string */
    private $url;
    /** @var array */
    private $params = [];
    /** @var array */
    private $headers = [];

    private $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HEADER => true
    );

    private $responseFormat = 'application/json';

    public function __construct($verb, $url, array $params = [])
    {
        $this->verb = $verb;
        $this->url = $url;
        $this->params = $params;
    }

    /**
     * {@inheritdoc}
     *
     * @return Request
     */
    public function setOptions(array $options)
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     *
     * @return ResponseInterface
     */
    public function send()
    {
        $verb = strtoupper($this->verb);
        $this->buildDefaultHeaders();

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt_array($curlHandler, $this->options);
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($curlHandler, CURLOPT_URL, UrlBuilder::getUrl($verb, $this->url, $this->params));

        if ($this->verb != 'GET') {
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $this->getBodyContent());
        }

        curl_setopt($curlHandler, CURLOPT_VERBOSE, true);
        $response = curl_exec($curlHandler);
        $info = curl_getinfo($curlHandler);
        $errno = curl_errno($curlHandler);
        $errorString = curl_error($curlHandler);
        curl_close($curlHandler);

        return new Response($response, $info, $errno, $errorString);
    }

    protected function getBodyContent()
    {
        return json_encode($this->params);
    }

    protected function buildDefaultHeaders()
    {
        $this->headers = array('Accept: ' . $this->responseFormat);
        array_push($this->headers, 'Content-Type: ' . $this->responseFormat);

        $data = $this->params;
        if (isset($data['oauth_token'])) {
            $oauth = $data['oauth_token'];
            array_push($this->headers, 'Authorization: OAuth ' . $oauth);
        }
    }
}