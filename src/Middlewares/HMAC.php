<?php
/**
 *
 * File         Hmac.php
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 */
namespace Ampersand\Middlewares;

use Ampersand\Auth\HmacManager;
use Exception;
use HttpException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Middleware;
use Symfony\Component\Yaml\Yaml;

/**
 *
 * Class        Hmac
 *
 * @package     Ampersand\Middlewares
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 *
 */
class Hmac extends Middleware
{
    const KEY_API_KEY   = 'apikey';
    const KEY_TIMESTAMP = 'timestamp';
    const KEY_TOKEN     = 'token';
    const KEY_PAYLOAD   = 'payload';
    const KEY_HMAC      = 'hmac';

    private $dataKeys = array(
        self::KEY_API_KEY,
        self::KEY_TIMESTAMP,
        self::KEY_TOKEN,
        self::KEY_PAYLOAD,
        self::KEY_HMAC,
    );

    /**
     * The object on which the getData... and setData... operates
     *
     * @var Request|Response
     */
    private $obj;

    /**
     * Key value pairs from config/hmac.yml
     * @var array
     */
    private $config;

    /**
     * Check HMAC
     *
     */
    public function call()
    {
        /*
         * #########################################
         * REQUEST
         */
        try {
            $this->checkRequestHmac();
        } catch (Exception $e) {
            // is it a unauthorized exception?
            if ($e instanceof HttpException) {
                // check code
                if ($e->getCode() === 401) {
                    throw $e;
                } else {
                    // throw server error
                    throw new HttpException('500', '500 Internal Server Error', 500);
                }
            } else {
                // throw server error
                throw new HttpException('500', '500 Internal Server Error', 500);
            }
        }

        /*
         * #########################################
         * Call next middleware
         */
        $this->next->call();


        /*
         * #########################################
         * RESPONSE
         *
         * first set the object to work on
         * and to be sure, we generate a new HMAC-Object
         */
        try {
            $this->setResponseHmac();
        } catch (Exception $e) {
            // throw server error
            throw new HttpException('500', '500 Internal Server Error', 500);
        }

    }


    private function getDataByKeyConfig($key)
    {
        $path = explode('|', $this->config['keys'][$key]);

        $data = '';

        if (!empty($path)) {
            // request | response
            if ($this->obj instanceof Request|| $this->obj instanceof Response)
                // headers | cookies
                if ($path[0] === 'headers'){
                    // key
                    $data = $this->obj->headers->get($path[1]);
                } elseif ($path[0] === 'cookies') {
                    $data = $this->obj->cookies->get($path[1]);
                } elseif ($path[0] === 'body') {
                    $data = $this->obj->getBody();
                }
            }

        return $data;
    }

    private function setDataByKeyConfig($key, $value)
    {
        $path = explode('|', $this->config['keys'][$key]);

        if (!empty($path)) {
            // request | response
            if ($this->obj instanceof Request|| $this->obj instanceof Response)
                // headers | cookies | body
                if ($path[0] === 'headers'){
                    $this->obj->headers->set($path[1], $value);
                } elseif ($path[0] === 'cookies') {
                    $this->obj->cookies->set($path[1], $value);
                } elseif ($path[0] === 'body' && $this->obj instanceof Response) {
                    $this->obj->setBody($value);
                }
        }

        return $this;
    }

    public function getDataByConfig()
    {
        $data = array();
        foreach ($this->dataKeys as $key) {
            $entry = $this->getDataByKeyConfig($key);
            if (!empty($entry)){
                $data[$key] = $entry;
            }
        }

        return $data;
    }

    public function setDataByConfig($data)
    {
        $keys = $this->config['keys'];

        foreach($keys as $key) {
            if (isset($data[$key])) {
                $this->setDataByKeyConfig($key, $data[$key]);
            }
        }

        return $this;
    }

    /**
     * Returns a HMAC-object with private key and time to life preset from config
     *
     * @return \Ampersand\Auth\HmacManager
     */
    private function factory_hmac()
    {
        $this->config = Yaml::parse('config/hmac.yml');
        /** @var \Ampersand\Auth\HmacManager $hmac */
        $hmac = new HmacManager();
        $hmac->setPrivateKey($this->config['privateKey']);
        $hmac->setTtl($this->config['ttl']);

        return $hmac;
    }

    /**
     * Check if a array key contains data
     * @param $data
     * @param $key
     *
     * @return bool
     */
    private function check_data_field($data, $key)
    {
        return (isset($data[$key]) && $data[$key] !== '' && !empty($data[$key]));
    }

    /**
     * Validate the Request
     *
     * @throws \HttpException
     */
    private function checkRequestHmac()
    {
        // set the working object to request
        $this->obj = $this->app->request;
        $hmac      = $this->factory_hmac();

        // get data from the request object
        $data = $this->getDataByConfig();

        // set data for validation

        // set the client api key
        $hmac->setApiKey($data['apikey']);

        // set the timestamp that was used by the client
        $hmac->setTimestamp($data['timestamp']);

        // set the token that identifies the client
        $hmac->setToken($data['token']);

        // set the payload that was used on clientside to create the hash
        $hmac->setPayload($data['payload']);

        // set the client hash
        $hmac->setHmacHash($data['hmac']);

        // verify
        // to check if the hmac is valid you need to run the isValid() method
        // this needs to be executed after the encode method has been ran
        if (!$hmac->isValid()) {
            // throw exception so the ApiError Middleware can handle it
            throw new HttpException('401', '401 Unauthorized', 401);
        }

        return true;
    }

    private function setResponseHmac()
    {
        // set the working object to response
        $this->obj = $this->app->response;
        $hmac = $this->factory_hmac();

        // get the payload from response
        $data = $this->getDataByConfig();

        // set the payload for hmac
        $hmac->setPayload($data['payload']);

        // hash the payload
        $hmac->create_and_set_hmac();

        // write timestamp to response
        $this->setDataByKeyConfig('timestamp', $hmac->getTimestamp());

        // write token to response
        $this->setDataByKeyConfig('token', $hmac->getToken());

        // write hmac hash to response
        $this->setDataByKeyConfig('hmac', $hmac->getHmacHash());

        // verify data was written
        $data = $this->getDataByConfig();
        if ($this->check_data_field($data, 'apikey')
            && $this->check_data_field($data, 'timestamp')
            && $this->check_data_field($data, 'token')
            && $this->check_data_field($data, 'hmac')
        ) {
        } else {
            throw new HttpException('500', '500 Internal Server Error', 500);
        }

        return $this;
    }
}