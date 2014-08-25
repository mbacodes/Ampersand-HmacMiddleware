<?php
/**
 *
 * File         HmacTest.php
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 */
namespace Ampersand\Tests\Middlewares;

use Ampersand\Middlewares\Hmac;
use Ampersand\Tests\HmacTestCase;
use Ampersand\Tests\SlimFrameworkTestCase;


/**
 *
 * Class        HmacTest
 *
 * @author      ${AUTHOR}
 * @copyright   ${COPYRIGHT}
 */
class HmacTest extends SlimFrameworkTestCase
{

    public function testCallMiddleware()
    {
        $app = $this->app;
        $app->add(new Hmac());
        $app->get('/foo', function () use ($app) {
            echo "Hello";
        });
        $app->run();
    }

    public function testNonValidHmacThrowsUnauthorizedException()
    {
        //
    }

    public function testCallsNextMidllewareOnValidHmac()
    {
        // init request and headers
    }
}
