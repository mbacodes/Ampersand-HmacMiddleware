<?php
/**
 *
 * File         HmacTest.php
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 */
namespace Ampersand\Tests\Middlewares;

use Ampersand\Middlewares\Hmac;
use Ampersand\Tests\MiddlewareTestCase;
use Ampersand\Tests\SlimFrameworkTestCase;


/**
 *
 * Class        HmacTest
 *
 * @author      Mathias Bauer <info@mbauer.eu>
 * @license     GPLv3
 */
class HmacTest extends MiddlewareTestCase
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
