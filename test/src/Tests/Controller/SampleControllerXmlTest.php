<?php

declare(strict_types=1);

/*
 * This file is part of the ApiTestCase package.
 *
 * (c) Łukasz Chruściel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiTestCase\Test\Tests\Controller;

use ApiTestCase\XmlApiTestCase;
use PHPUnit\Framework\AssertionFailedError;

class SampleControllerXmlTest extends XmlApiTestCase
{
    public function testGetHelloWorldResponse()
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'hello_world');
    }

    public function testGetHelloWorldIncorrectResponse()
    {
        $this->expectException(AssertionFailedError::class);

        $this->client->request('GET', '/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'incorrect_hello_world');
    }

    public function testGetHelloWorldWithMatcherResponse()
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'hello_matcher_world');
    }

    public function testGetHelloWorldWithWildCardResponse()
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'hello_wild_card');
    }

    public function testGetProductInventoryFromThirdPartyApi()
    {
        $this->client->getContainer()->mock('app.third_party_api_client', 'ApiTestCase\Test\Service\ThirdPartyApiClient')
            ->shouldReceive('getInventory')
            ->once()
            ->andReturn($this->getJsonResponseFixture('third_party_api_inventory'))
        ;

        $this->client->request('GET', '/use-third-party-api/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'use_third_party_api');
    }

    public function testProductIndexResponse()
    {
        $this->loadFixturesFromDirectory();

        $this->client->request('GET', '/products/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'product_index');
    }

    public function testCategoriesIndexResponse()
    {
        $this->loadFixturesFromFiles(['product.yml', 'category.yml']);

        $this->client->request('GET', '/categories/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'category_index');
    }

    public function testProductShowResponse()
    {
        $objects = $this->loadFixturesFromDirectory();

        $this->client->request('GET', '/products/' . $objects['product1']->getId());

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'get_product');
    }
}
