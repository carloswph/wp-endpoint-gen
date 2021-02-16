# wp-endpoint-gen
Wordpress custom endpoints helper and class generator. Wordpress engine to add new custom endpoints to the WP API is not that hard to understand. However, manipulate dozens of new endpoints and the respective callbacks and permission callbacks can be troublesome depending on the complexity of the plugin or extension. Thus, this library helps you not only by creating an OOP approach to add new endpoints, but also for managing their namespaces, arguments and callbacks. 
Besides, we thought that'd be much simpler and organized if we concentrated callbacks and permissions for each of the new endpoints in a particular controller class - and, of course, automatically generate them depending on how you customize your new endpoint.
Well, nothing else to add by now - let's check how it works within the following code samples.

# Installation

# Usage
The first step involves creating a new instance of the class WPH\Endpoints\Config in your code. Do not forget to make it instantiable using Composer's autoload. The Config class can either be instantiated within the Generator class, or before it, everytime you desire to change the default values for the config details.
## Configuring what?
By default, we considered all custom endpoints to be positioned within the namespace ``wph`` (for obvious reasons). Generated classes will be created under the namespace or Psr "EndpointGroup", and will bring "v1" as the current version. The base path for saving the generated classes will be located onto a new folder named "endpoints", inside the ``wp-content`` directory.
Brilliant, ain't it? No? Don't worry, by instantiating the Config class, you are able to customize all of those variables.

```php
use WPH\Endpoints\Config;

require __DIR__ . '/vendor/autoload.php';

$config = new Config(); // Config class instance.
$config->setPsr('Foo'); // Sets generated classes' namespaces as Foo
$config->setPath( WP_CONTENT_DIR . '/foo'); // Creates and sets the generated classes' path 
$config->setVersion('v1.2'); // Sets a new version for all endpoints
$config->setNamespace('foo-api'); // Sets a new namespace for the WP API endpoints (not the classes' namespace)

```

## I loved your defaults - don't need to change it
Brilliant again! In this case, just ignore the Config class set up, and go to the next part of this tutorial. You'll still need to use the Config class, but if you don't need to change anything, you should just inject a new instance of it within the Generator class. The next example will clarify.

```php
use WPH\Endpoints\Generator;

require __DIR__ . '/vendor/autoload.php';

// If you instantiated the Config class and set up new values for the variables, just use the generator including three arguments: the endpoint name, an array of all http methods to be allowed on it and, finally, the object of the Config class:
$gen = new Generator('wph-endpoint', ['GET', 'PUT'], $config); // That's it. The new endpoints have been created inside your WP API. 
$gen->generate(); // Now the magic: check the configured path or, if you just used ours, look inside the `wp-content/endpoints` directory.
$gen->autoload(); // Once callbacks and permission callbacks have been set up as external classes, we need to autoload them.

```

All custom endpoints will find the respective callbacks and permission callbacks inside their own classes, located in the path you indicated. It's more than just thinking the WP endpoints in an OOP way - that means you DON'T NEED to write dozens of repetitive functions and callbacks, nor pollute your code with huge anonymous functions or similars.

## Ok, but you guys forgot the endpoint arguments...
Uhhh... no, actually we did not. By the way, you can add args to the endpoints using two different methods.
1. If all endpoint methods shall be accepting the same arguments, add them as an array, while instantiating the Generator class (underway)
2. However, if you need to add arguments just for the GET method, for instance, you can apply the chain method addArgs().

Let's start with the method 2, which is already functional. All you need to do is using the chained method addArgs(), with two paramets: the http method (GET, for instance), and an array of the desired arguments. Like this:

```php
$gen = new Generator('wph-endpoint', ['GET', 'PUT'], $config); 
$gen->addArgs('PUT', ['id' => ['description' => 'Id of something']]);
$gen->addArgs('GET', ['name' => ['description' => 'Name of someone']]);

```

## How generated classes look like?
So now you are probably asking yourself how those generated controllers we mentioned look like. Of course they are basically skeletons in which you can create your own processes and conditions, but they are already functional from the first moment, returning a simple instance of WP_Rest_Response class. Let's consider an endpoint added under the name 'boxes', and dealing with GET, POST and HEAD calls. In this case, the generated controller class would appear like this:

```php

namespace Foo\Routes;
/**
 * Controller class for callbacks and permissions.
 * Route --> Foo\Routes\Boxes
 * @since 1.0.0
 */
class Boxes
{
	/**
	 * Handles GET requests to the endpoint.
	 * @return \WP_Rest_Response
	 */
	public function getBoxes(\WP_Rest_Request $request)
	{
		return new \WP_Rest_Response();
	}


	/**
	 * Handles POST requests to the endpoint.
	 * @return \WP_Rest_Response
	 */
	public function postBoxes(\WP_Rest_Request $request)
	{
		return new \WP_Rest_Response();
	}


	/**
	 * Handles HEAD requests to the endpoint.
	 * @return \WP_Rest_Response
	 */
	public function headBoxes(\WP_Rest_Request $request)
	{
		return new \WP_Rest_Response();
	}


	/**
	 * Authenticate or limitate requests to the endpoint.
	 * @return bool
	 */
	public function permissions(\WP_Rest_Request $request)
	{
		// Your conditions.
		return true;
	}
}

```