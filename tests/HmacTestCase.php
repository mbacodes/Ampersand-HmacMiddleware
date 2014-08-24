<?php
/**
 *
 * File         HmacTestCase.php
 *
 * @author      ${AUTHOR}
 * @copyright   ${COPYRIGHT}
 */
namespace Ampersand\Tests;
use Xpmock\TestCaseTrait;

/**
 *
 * Class        HmacTestCase
 *
 * @package     Ampersand\Tests
 *
 */
class HmacTestCase extends \PHPUnit_Framework_TestCase
{
    use TestCaseTrait;

    /**
     * @var \Ampersand\Http\HeadersInterface
     */
    protected $headers;

    /**
     * @var \Ampersand\Http\RequestInterface
     */
    protected $request;

    /**
     * @var \Ampersand\Http\ResponseInterface
     */
    protected $response;

    /**
     * @var \Ampersand\Http\CookiesInterface
     */
    protected $cookies;


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
        // Mock the Headers
        $headers       = $this->getMock('\Ampersand\Http\HeadersInterface');
        $this->headers = $headers;

        // Mock the Request
        $request       = $this->getMock('\Ampersand\Http\RequestInterface');
        //$request->injectTo($this->headers, 'headers');
        $this->request = $request;

        // Mock the Response
        $response = $this->getMock('\Ampersand\Http\ResponseInterface');
        //$response->headers($this->headers);
        $this->response = $response;

        // Mock the cookies
        $this->cookies = $this->getMock('\Ampersand\Http\CookiesInterface');

    }

    /**
     * Test if $this->headers is an instance of Ampersand\Http\HeadersInterface
     */
    public function testHeadersIsInstanceOfHeadersInterface()
    {
        $this->assertInstanceOf('\Ampersand\Http\HeadersInterface', $this->headers);
    }

    /**
     * Test if $this->request is an instance of Ampersand\Http\RequestInterface
     */
    public function testRequestIsInstanceOfRequestInterface()
    {
        $this->assertInstanceOf('\Ampersand\Http\RequestInterface', $this->request);
    }

    /**
     * Test if $this->response is an instance of Ampersand\Http\ResponseInterface
     */
    public function testResponseIsInstanceOfResponseInterface()
    {
        $this->assertInstanceOf('\Ampersand\Http\ResponseInterface', $this->response);
    }

    /**
     * Test if $this->cookies is an instance of Ampersand\Http\CookiesInterface
     */
    public function testCookiesIsInstanceOfCookiesInterface()
    {
        $this->assertInstanceOf('\Ampersand\Http\CookiesInterface', $this->cookies);
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


}