<?php
namespace Awageeks\Bpjs;

use GuzzleHttp\Client;

class BpjsService
{
    /**
     * Guzzle HTTP Client object
     * @var \GuzzleHttp\Client
     */
    private $clients;

    /**
     * Request headers
     * @var array
     */
    private $headers;

    /**
     * X-cons-id header value
     * @var int
     */
    private $cons_id;

    /**
     * X-Timestamp header value
     * @var string
     */
    private $timestamp;

    /**
     * X-Signature header value
     * @var string
     */
    private $signature;

    /**
     * X-Authorization header value
     * @var string
     */
    private $authorization;

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $app_code;

    /**
     * @var string
     */
    private $base_url;

    /**
     * @var string
     */
    private $service_name;

    /**
     * @var string
     */
    protected $feature;

    public function __construct($configurations = [])
    {
        $this->clients = new Client([
            'verify' => false
        ]);

        // merge configs
        $configurations = config('bpjs') + $configurations;

        foreach ($configurations as $key => $val){
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }

        //set X-Timestamp, X-Signature, and finally the headers
        $this->setTimestamp()->setSignature()->setAuthorization()->setHeaders();
    }

    public function index($start = null, $limit = null)
    {
        $feature = $this->feature;
        if($start !== null and $limit !== null) {
            $response = $this->get("{$feature}/{$start}/{$limit}");
        } else {
            $response = $this->get("{$feature}");
        }
        return json_decode($response, true);
    }

    public function show($keyword, $start = null, $limit = null)
    {
        $feature = $this->feature;
        if($start !== null and $limit !== null) {
            $response = $this->get("{$feature}/{$keyword}/{$start}/{$limit}");
        } else {
            $response = $this->get("{$feature}/{$keyword}");
        }
        return json_decode($response, true);
    }

    public function store($data = [])
    {
        $response = $this->post($this->feature, $data);
        return json_decode($response, true);
    }

    public function update($data = [])
    {
        $response = $this->put($this->feature, $data);
        return json_decode($response, true);
    }

    public function destroy($keyword)
    {
        $response = $this->delete($this->feature, $keyword);
        return json_decode($response, true);
    }

    protected function setHeaders()
    {
        $this->headers = [
            'X-cons-id'       => $this->cons_id,
            'X-Timestamp'     => $this->timestamp,
            'X-Signature'     => $this->signature,
            'X-Authorization' => $this->authorization,
        ];
        return $this;
    }

    protected function setTimestamp()
    {
        date_default_timezone_set('UTC');
        $this->timestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        return $this;
    }

    protected function setSignature()
    {
        $data = "{$this->cons_id}&{$this->timestamp}";
        $signature = hash_hmac('sha256', $data, $this->secret_key, true);
        $encodedSignature = base64_encode($signature);
        $this->signature = $encodedSignature;
        return $this;
    }

    protected function setAuthorization()
    {
        $data = "{$this->username}:{$this->password}:{$this->app_code}";
        $encodedAuth = base64_encode($data);
        $this->authorization = "Basic {$encodedAuth}";
        return $this;
    }

    protected function getClients()
    {
        return $this->clients;
    }

    protected function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    protected function getHeaders()
    {
        return $this->headers;
    }

    protected function getBaseUrl()
    {
        return $this->base_url;
    }

    protected function getServiceName()
    {
        return $this->service_name;
    }

    protected function get($feature)
    {
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        try {
            $response = $this->clients->request(
                'GET',
                "{$this->base_url}/{$this->service_name}/{$feature}",
                [
                    'headers' => $this->headers
                ]
            )->getBody()->getContents();
        } catch (\Exception $e) {
            $response = $e->getResponse()->getBody();
        }
        return $response;
    }

    protected function post($feature, $data = [], $headers = [])
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Accept'] = 'application/json';
        if(!empty($headers)){
            $this->headers = array_merge($this->headers,$headers);
        }
        try {
            $response = $this->clients->request(
                'POST',
                "{$this->base_url}/{$this->service_name}/{$feature}",
                [
                    'headers' => $this->headers,
                    'body'    => json_encode($data),
                ]
            )->getBody()->getContents();
        } catch (\Exception $e) {
            $response = $e->getResponse()->getBody();
        }
        return $response;
    }

    protected function put($feature, $data = [])
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Accept'] = 'application/json';
        try {
            $response = $this->clients->request(
                'PUT',
                "{$this->base_url}/{$this->service_name}/{$feature}",
                [
                    'headers' => $this->headers,
                    'body'    => json_encode($data),
                ]
            )->getBody()->getContents();
        } catch (\Exception $e) {
            $response = $e->getResponse()->getBody();
        }
        return $response;
    }

    protected function delete($feature, $id)
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Accept'] = 'application/json';
        try {
            $response = $this->clients->request(
                'DELETE',
                "{$this->base_url}/{$this->service_name}/{$feature}/{$id}",
                [
                    'headers' => $this->headers,
                ]
            )->getBody()->getContents();
        } catch (\Exception $e) {
            $response = $e->getResponse()->getBody();
        }
        return $response;
    }
}
