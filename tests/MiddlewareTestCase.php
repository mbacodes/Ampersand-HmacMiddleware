<?php
/**
 *
 * File         HmacTestCase.php
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 */
namespace Ampersand\Tests;
use Ampersand\Middlewares\Hmac;
use Slim\Environment;
use Slim\Slim;
use Xpmock\TestCaseTrait;

/**
 *
 * Class        HmacTestCase
 *
 * @package     Ampersand\Tests
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 *
 */
class MiddlewareTestCase extends \Xpmock\TestCase
{
    use TestCaseTrait;

    protected $env = array(
        'SCRIPT_NAME' => '/index.php',
        'PATH_INFO'   => '/foo',
        'HTTP_COOKIE' => 'slim_session=1644004961%7CLKkYPwqKIMvBK7MWl6D%2BxeuhLuMaW4quN%2F512ZAaVIY%3D%7Ce0f007fa852c7101e8224bb529e26be4d0dfbd63',
    );

    /**
     * @var \Slim\Slim
     */
    protected $app;


    /**
     * @var \Slim\Http\Headers
     */
    protected $headers;

    /**
     * @var \Slim\Http\Request
     */
    protected $request;

    /**
     * @var \Slim\Http\Response
     */
    protected $response;

    /**
     * @var \Slim\Http\Cookies
     */
    protected $cookies;

    /**
     * @var \Ampersand\Auth\HmacManager
     */
    protected $hmacManager;


    // We support these methods for testing. These are available via
    // `this->get()` and `$this->post()`. This is accomplished with the
    // `__call()` magic method below.
    private $testingMethods = array('get', 'post', 'patch', 'put', 'delete', 'head');

    // Run for each unit test to setup our app environment
    /**
     * Mock headers, request and response
     */
    public function setup()
    {
        // Mock the Slim Env
        $this->setupSlimApp();
        // Establish a local reference to the Slim app object
       // $this->app = $this->mock('\Slim\Slim');

        // Mock the Headers
        $headers       = $this->mock('\Slim\Http\Headers');
        $this->headers = $headers;

        // Mock the Request
        $request       = $this->mock('\Slim\Http\Request');
        //$this->injectTo($this->headers, 'headers');
        $this->request = $request;

        // Mock the Response
        $response = $this->mock('\Slim\Http\Response');
        // $response->injectTo($this->headers, 'headers');
        $this->response = $response;

        // Mock the cookies
        $this->cookies = $this->mock('\Slim\Http\Cookies');

        // Mock the HmacManager
        $this->hmacManager = $this->mock('\Ampersand\Auth\HmacManager');
    }

    /**
     * Test if new Hmac() returns a new instance of \Slim\Middleware
     */
    public function testHmacIsInstanceOfSlimMiddleware()
    {
        $this->assertInstanceOf('\Slim\Middleware', new Hmac());
    }

    /**
     * Test if $this->hmacManager is an instance of \Ampersand\Auth\HmacManager
     */
    public function testHmacIsInstanceOfAmpersandAuthHmacManager()
    {
        $this->assertInstanceOf('\Ampersand\Auth\HmacManager', $this->hmacManager);
    }



    // Abstract way to make a request to SlimPHP, this allows us to mock the
    // slim environment
    /**
     * @todo-comment
     * @todo-implement
     */
    private function setRequest($method, $path, $formVars = array(), $optionalHeaders = array())
    {

    }

    /**
     * @todo-comment
     * @todo-implement
     */
    public function setResponse()
    {

    }

    /**
     * @todo-comment
     * @todo-implement
     */
    public function setCookies()
    {

    }


    // Implement our `get`, `post`, and other http operations
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->testingMethods)) {
            list($path, $formVars, $headers) = array_pad($arguments, 3, array());

            return $this->setRequest($method, $path, $formVars, $headers);
        }
        throw new \BadMethodCallException(strtoupper($method) . ' is not supported');
    }

    /**
     * assertException extension from @see https://gist.github.com/VladaHejda/8826707
     *
     * @param callable $callback
     * @param string   $expectedException
     * @param null     $expectedCode
     * @param null     $expectedMessage
     */
    protected function assertException(callable $callback, $expectedException = 'Exception', $expectedCode = null, $expectedMessage = null)
    {
        if (!class_exists($expectedException) || interface_exists($expectedException)) {
            $this->fail("An exception of type '$expectedException' does not exist.");
        }

        try {
            $callback();
        } catch (\Exception $e) {
            $class   = get_class($e);
            $message = $e->getMessage();
            $code    = $e->getCode();

            $extraInfo = $message ? " (message was $message, code was $code)" : ($code ? " (code was $code)" : '');
            $this->assertInstanceOf($expectedException, $e, "Failed asserting the class of exception $extraInfo.");

            if (null !== $expectedCode) {
                $this->assertEquals($expectedCode, $code, "Failed asserting code of thrown $class.");
            }
            if (null !== $expectedMessage) {
                $this->assertContains($expectedMessage, $message, "Failed asserting the message of thrown $class.");
            }

            return;
        }

        $extraInfo = $expectedException !== 'Exception' ? " of type $expectedException" : '';
        $this->fail("Failed asserting that exception $extraInfo was thrown.");
    }

    protected function setupSlimApp($env = null, $appConfig = null)
    {
        if ($env === null) {
            $env = $this->env;
        }
        Environment::mock($env);
        // Initialize our own copy of the slim application
        if ($appConfig === null) {
            $appConfig = array(
                'version'        => '0.0.0',
                'debug'          => false,
                'mode'           => 'testing',
                'templates.path' => __DIR__ . '/../app/templates'
            );
        }
        $this->app = new Slim($appConfig);
    }


}