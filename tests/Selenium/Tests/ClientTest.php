<?php
/*
 * This file is part of PHP Selenium Library.
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Selenium\Tests;

use Buzz\Client\Mock\FIFO;
use Buzz\Message\Request;
use Buzz\Message\Response;

use Selenium\Capabilities;
use Selenium\Client;

/**
 * Tests for the client object.
 *
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Verify the actual call to the server for creating a session
     */
    public function testCreateSession()
    {
        $buzzClient = new FIFO();
        $client = new Client('http://localhost', $buzzClient);

        $response = new Response();
        $response->addHeader('1.0 302 Moved Temporarly');
        $response->addHeader('Location: http://localhost/session/12345');
        $buzzClient->sendToQueue($response);

        $session = $client->createSession(new Capabilities('firefox'));

        $this->assertEquals(0, count($buzzClient->getQueue()), "Queue is empty");

        $this->assertEquals('12345', $session->getSessionId());
    }

    /**
     * Verify it's possible to fetch an existing session
     */
    public function testGetSession()
    {
        $buzzClient = new FIFO();
        $client = new Client('http://localhost', $buzzClient);

        $response = new Response();
        $response->addHeader('1.0 302 Moved Temporarly');
        $response->addHeader('Location: http://localhost/session/12345');
        $buzzClient->sendToQueue($response);

        $session = $client->createSession(new Capabilities('firefox'));

        $this->assertEquals('12345', $session->getSessionId());
        $this->assertEquals($session, $client->getSession('12345'));

        try
        {
            $client->getSession('54321');
            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertEquals('The session "54321" was not found', $e->getMessage());
        }
    }

    /**
     * Verify session close
     */
    public function testCloseSession()
    {
        $buzzClient = new FIFO();
        $client = new Client('http://localhost', $buzzClient);

        $response = new Response();
        $response->addHeader('1.0 200 OK');
        $buzzClient->sendToQueue($response);

        $session = $client->closeSession('12345');

        $this->assertEquals(0, count($buzzClient->getQueue()));
    }

    /**
     * Verify the prefix is inserted in request
     */
    public function testPrefix()
    {
        $buzzClient = new FIFO();
        $buzzClient->sendToQueue(new Response());

        $client = new Client('http://localhost/prefix', $buzzClient);

        $request = new Request();
        $request->setResource('/session');
        $response = new Response();

        $client->process($request, $response);

        $this->assertEquals('/prefix/session', $request->getResource());
    }
}
