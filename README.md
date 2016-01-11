ApiTestCase
===========

[![Build Status](https://travis-ci.org/Lakion/ApiTestCase.svg?branch=master)](https://travis-ci.org/Lakion/ApiTestCase)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Lakion/ApiTestCase/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Lakion/ApiTestCase/?branch=master)

**ApiTestCase** is a PHPUnit TestCase that will make your life as a Symfony2 API developer much easier. It extends basic [Symfony2](https://symfony.com/) WebTestCase with some cool features. 

Thanks to [PHP-Matcher](https://github.com/coduo/php-matcher) you can, according to its readme, "write expected json responses like a gangster". We definitely agree.

[SymfonyMockerContainer](https://github.com/PolishSymfonyCommunity/SymfonyMockerContainer) makes it super easy to mock services, which is great if you work on an application that communicates with other APIs, for example: Google Maps API.

It also uses [Alice](https://github.com/nelmio/alice) for easy Doctrine fixtures loading.

Features:

* Clear TDD workflow for API development with Symfony;
* JSON/XML matching with clear error messages;
* Fixtures loading with Alice *(optional)*;
* Easy mocking of Symfony services, which makes it easier to work on SOA-based projects;

Installation
------------

Assuming you already have Composer installed globally:

```bash
$ composer require --dev lakion/api-test-case
```

Then you have to slightly change your Kernel logic to support SymfonyMockerContainer:

```php
// app/AppKernel.php

protected function getContainerBaseClass()
{
    if ('test' === $this->environment) {
        return '\PSS\SymfonyMockerContainer\DependencyInjection\MockerContainer';
    }

    return parent::getContainerBaseClass();
}
```

And it's done! ApiTestCase is working with the default configuration.
 
Usage
-----

We provide two base classes for your test cases: JsonApiTestCase and the XmlApiTestCase. Choose one based on the format of the API you want to create.

### Json Example

The basic TDD workflow is the following:

1. Write a test case that sends the request and use ``assertResponse`` assertion method to check if response contents are matching your expectations. You need a name for the response file;
2. Create the file with name that you picked in step 1. and put expected response contents there. It should be put in ``src/AppBundle/Tests/Responses/Expected/hello_world.json`` for example.
3. Make it red.
4. Make it green.
5. Refactor.

Let's see a simple example! Write the following test:

```php

namespace AppBundle\Tests\Controller\HelloWorldTest;

use Lakion\ApiTestCase\JsonApiTestCase;

class HelloWorldTest extends JsonApiTestCase
{
    public function testGetHelloWorldResponse()
    {
        $this->client->request('GET', '/');

        $response = $this->client->getResponse();

        $this->assertResponse($response, 'hello_world');
    }
}
```

Now define the expected response file:

```json
{
    "message": "Hello ApiTestCase World!"
}
```

Run your tests:

```bash
$ bin/phpunit
```

Your test should fail with some errors, you are probably missing the controller and routing, so go ahead and define them!
As soon as you implement your Controller and configure appropriate routing, you can run your tests again:

If the response contents will match our expectations, console will present a simple message:

```bash
OK (1 tests, 2 assertions)
```

Otherwise it will present diff of received messages:

```bash
"Hello ApiTestCase World" does not match "Hello ApiTestCase World!".
@@ -1,4 +1,3 @@
 {
-    "message": "Hello ApiTestCase World!"
+    "message": "Hello ApiTestCase World"
 }
-
```

Firstly, function `assertResponse` will check the response code (200 is a default response code), then it will check if header of response contains `application/json` content type. At the end it will check if the response contents matches the expectation.
Sometimes you can't predict some values in the response, for example auto-generated date or id from the database. No magic is needed here because [PHP-Matcher](https://github.com/coduo/php-matcher) comes with a helping hand. These are just a few examples of available patterns:

* ``@string@``
* ``@integer@``
* ``@boolean@``
* ``@array@``

Check for more on [PHP-Matcher's documentation](https://github.com/coduo/php-matcher). 

With these patterns your expected response will look like this:

```json
{
    "message": "@string@"
}
```

With this in place, any string under key `message` will match the pattern. More complicated expected response could look like this:

```json
[
    {
        "id": "@integer@",
        "name": "Star-Wars T-shirt",
        "sku": "SWTS",
        "price": 5500,
        "sizes": "@array@",
        "created_at": "@string@.isDateTime()"
    },
    {
        "id": "@integer@",
        "name": "Han Solo Mug",
        "sku": "HSM",
        "price": 500,
        "sizes": "@array@",
        "created_at": "@string@.isDateTime()"
    }
]
```

And will match the following list of products:

```php
array(
    array(
        'id' => 1,
        'name' => 'Star-Wars T-shirt',
        'sku' => 'SWTS',
        'price' => 5500,
        'sizes' => array('S', 'M', 'L'),
        'created_at' => new \DateTime(),
    ),
    array(
        'id' => 2,
        'name' => 'Han Solo Mug',
        'sku' => 'HSM',
        'price' => 500,
        'sizes' => array('S', 'L'),
        'created_at' => new \DateTime(),
    ),
)
```

It is also a really common case to communicate with some external API. But in test environment we want to be sure what we will receive from it. To check behaviour of our app with different responses from external API we can use [SymfonyMockerContainer](https://github.com/PolishSymfonyCommunity/SymfonyMockerContainer). This library allows to mock the third party API response, and asserts number of calls.
Again, this is extra useful when you work with APIs like Google Maps, Stripe etc. You can also mock response from other apps in your SOA project.

```php
    public function testGetResponseFromMockedService()
    {
        $this->client->getContainer()->mock('app.third_party_api_client', 'Lakion\ApiTestCase\Test\Service\ThirdPartyApiClient')
            ->shouldReceive('getInventory')
            ->once()
            ->andReturn($this->getJsonResponseFixture('third_party_api_inventory'))
        ;
    }
```

From this moment, first `getInventory` method call will return the response defined in `third_party_api_inventory.json` file placed in a ``src/AppBundle/Tests/Responses/Mocked/`` folder, or any other location you have defined in ``phpunit.xml`` file.

### Testing With Database Fixtures

ApiTestCase is integrated with ``nelmio/alice``. Thanks to this nice library you can easily load your fixtures when you need them. You have to define your fixtures and place them in an appropriate directory.
Here is some example how to define your fixtures and use case. For more information how to define your fixtures check [Alice's documentation](https://github.com/nelmio/alice). 

Let's say you have a mapped Doctrine entity called Book in your application: 

```php
    class Book 
    {
        private $id;
        private $title;
        private $author;
    
        // ... 
    }
```

To load fixtures for the test, you need to define a simple ``YAML`` file in ``src/AppBundle/Tests/DataFixtures/ORM/books.yml``:

```yml
    Lakion\ApiTestCase\Test\Entity\Book:
        book1:
            name: "Lord of The Rings"
            author: "J. R. R. Tolkien"
        book2:
            name: "Game of Thrones"
            price: "George R. R. Martin"
```

Finally, to use these fixtures in a test, just call a proper method:

```php
    public function testBooksIndexAction()
    {
        // This method require subpath to locate specific fixture file in your DataFixtures/ORM directory.
        $this->loadFixturesFromFile('books.yml');  
      
        // There is another method that allows you to load fixtures from directory.
        $this->loadFixturesFromDirectory('big_library');
    }
```

Configuration Reference
-----------------------

To customize your test suite configuration you can add a few more options to phpunit.xml:

```xml
<php>
    <server name="KERNEL_DIR" value="/path/to/dir/with/kernel" />
    <server name="KERNEL_CLASS_PATH" value="/path/to/kernel/class" />
    <server name="EXPECTED_RESPONSE_DIR" value="/path/to/expected/responses/" />
    <server name="MOCKED_RESPONSE_DIR" value="/path/to/mocked/responses/" />
    <server name="FIXTURES_DIR" value="/path/to/DataFixtures/ORM/" />
    <server name="OPEN_ERROR_IN_BROWSER" value="true/false" />
    <server name="OPEN_BROWSER_COMMAND" value="open %s" />
    <server name="IS_DOCTRINE_ORM_SUPPORTED" value="true/false" />
</php>
```

 * `KERNEL_DIR` variable contains a path to kernel of your project. If not set, WebTestCase will look for AppKernel in the folder where you have your phpunit.xml file.
 * `KERNEL_CLASS_PATH` allows you to specify exactly which class in which folder should be used in order to setup the Kernel. 
 * `EXPECTED_RESPONSE_DIR` and `MOCKED_RESPONSE_DIR` variables contain paths to folders with expected and mocked responses. `EXPECTED_RESPONSE_DIR` is used when API result is compared with existing json file. `MOCKED_RESPONSE_DIR` should contains files with mocked responses from outside API's. Both variable can have same value but we recommend to keep it separated. If these values aren't set, ApiTestCase will try to guess location of responses. It will try to look for the responses in a following folders '../Responses/Expected' and '../Responses/Mocked' relatively located to your controller test class.
 * `FIXTURES_DIR` variable contains a path to folder with your data fixtures. By default if this variable isn't set it will search for `../DataFixtures/ORM/` relatively located to your test class . ApiTestCase throws RunTimeException if folder doesn't exist or there won't be any files to load.
 * `OPEN_ERROR_IN_BROWSER` is a flag which turns on displaying error in a browser window. The default value is false.
 * `OPEN_BROWSER_COMMAND` is a command which will be used to open browser with an exception.
 * `IS_DOCTRINE_ORM_SUPPORTED` is a flag which turns on doctrine support includes handy data fixtures loader and database purger.

Sample Project
--------------

In the ``test/`` directory, you can find sample Symfony2 project with minimal configuration required to use this library.

### Testing

In order to run our PHPUnit tests suite, execute following commands:

```bash
$ composer install
$ test/app/console doctrine:database:create
$ test/app/console doctrine:schema:create
$ bin/phpunit test/
```

Bug Tracking and Suggestions
----------------------------

If you have found a bug or have a great idea for improvement, please [open an issue on this repository](https://github.com/Lakion/ApiTestCase/issues/new).

Versioning
----------

Releases will be numbered with the format `major.minor.patch`.

And constructed with the following guidelines.

* Breaking backwards compatibility bumps the major.
* New additions without breaking backwards compatibility bumps the minor.
* Bug fixes and misc changes bump the patch.

For more information on SemVer, please visit [semver.org website](http://semver.org/).

MIT License
-----------

License can be found [here](https://github.com/Lakion/ApiTestCase/blob/master/LICENSE).

Authors
-------

The bundle was originally created by:

* Łukasz Chruściel <lukasz.chrusciel@lakion.com>, 
* Michał Marcinkowski <michal.marcinkowski@lakion.com>
* Paweł Jędrzejewski <pawel.jedrzejewski@lakion.com>
* Arkadiusz Krakowiak <arkadiusz.krakowiak@lakion.com>

See the list of [contributors](https://github.com/Lakion/ApiTestCase/graphs/contributors).
