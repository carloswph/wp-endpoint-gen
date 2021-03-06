<?php

namespace WPH\Endpoints;
use Nette\PhpGenerator\ClassType as CT;
use Nette\Loaders\RobotLoader as AL;

class Generator {

	/**
	 * Endpoint name.
	 * 
	 * @var  string
	 */
	protected $endpoint;
	/**
	 * Methods allowed in this endpoint
	 * 
	 * @var  array
	 * @example array('POST', 'PUT', 'HEAD')
	 */
	protected $method;
	/**
	 * Namespace for the current endpoint group. Passed through the Config class instance.
	 *
	 * @var  string
	 */
	protected $namespace;
	/**
	 * Instance of the Config class.
	 *
	 * @var  object
	 */
	protected $config;
	/**
	 * Path to generated controller classes. Passed through the Config class instance.
	 *
	 * @var  string
	 */
	protected $path;
	/**
	 * Version of the created endpoints. Passed through the Config class instance.
	 *
	 * @var  string
	 */
	protected $version;
	/**
	 * Arguments to be implemented (optional). Use to apply all arguments to all methods - if looking for arguments applied individually to each method, use the function addArgs()
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 * 
	 * @param  string  $endpoint  Name of the endpoint.
	 * @param  array  $method  Method or methods allowed for the current endpoint. Always submit as an array.
	 * @param  object  $config  Instance of the Config class.
	 * @param  array  $args  Array of arguments to be applied to all methods in the current endpoint.
	 */
	public function __construct(string $endpoint, array $method, Config $config, $args = null) {

		$this->endpoint = $endpoint;
		$this->method = $method;
		$this->config = $config;
		$this->namespace = $this->config->getNamespace();
		$this->path = $this->config->getPath();
		$this->version = $this->config->getVersion();

		// Currently not operational
		if(!is_null($args)) {
			$this->args = $this->array_adjust($method, $args);
		}

		add_action('rest_api_init', array($this, 'route'));
	}

	/**
	 * Route generator.
	 *
	 * @since  1.0.0
	 * @return  void
	 * 
	 */
	public function route() {

		// Iterates to generate the different endpoints for each added HTTP method
		foreach ($this->method as $httpMethod) {
			register_rest_route(
				$this->namespace . '/' . $this->version,
				$this->endpoint,
				array(
					'methods' => $httpMethod,
					'callback' => $this->getCallbackClass($this->endpoint, $httpMethod),
					'permission_callback' => $this->getPermissionsClass($this->endpoint),
					'args' => $this->getArgs($httpMethod)
				)
			);
		}
	}

	/**
	 * Class generator for the endpoint callbacks and permission callbacks.
	 *
	 * @since  1.0.0
	 * @return  void
	 */
	public function generate() {

		// Create configured directory if it doesn't exist
		if (!is_dir($this->path)) {
			mkdir($this->path, 0755, true);
		}

		// Check if file already exists before proceeding
		if(!is_file($this->path . '/' . ucfirst($this->endpoint) . '.php')) {

			$class = new CT(ucfirst($this->endpoint));

			$class
				->addComment("Controller class for callbacks and permissions.\nRoute --> " . sprintf($this->config->getPsr() . '\%s', ucfirst($this->endpoint)))
				->addComment('@since ' . $this->config->getVersion());

			// Iterates callback creation for all methods allowed
			foreach ($this->method as $function) {
				$function = $class->addMethod(strtolower($function) . ucfirst($this->endpoint))
					->addComment('Handles ' . $function . ' requests to the endpoint.')
					->addComment('@return \WP_Rest_Response')
					->setBody('return new \WP_Rest_Response();');

				$function->addParameter('request')
					->setType('\WP_Rest_Request');
			}

			// Permissions callback is a single function per controller class
			$permissions = $class->addMethod('permissions')
					->addComment('Authenticate or limitate requests to the endpoint.')
					->addComment('@return bool')
					->setBody("// Your conditions.\nreturn true;");

				$permissions->addParameter('request')
					->setType('\WP_Rest_Request');

			// to generate PHP code simply cast to string or use echo:
			$controller = fopen($this->config->getPath() . ucfirst($this->endpoint) . '.php', 'w+');
			fwrite($controller, "<?php\n\nnamespace " . $this->config->getPsr() . ";\n");
			fwrite($controller, $class);
			fclose($controller);

		}
	}

	/**
	 * Get the class and callback function based on the endpoint and method.
	 *
	 * @since  1.0.0
	 * @param  string  $endpoint  Name of the endpoint.
	 * @param  string  $method  HTTP method.
	 *
	 * @return  array  $callback
	 */
	public function getCallbackClass($endpoint, $method) {

		$classname = ucfirst($endpoint);
		$class = sprintf($this->config->getPsr() . '\%s', $classname);
		$function = strtolower($method) . $classname;

		$callback = array($class, $function);

		return $callback;
	}

	/**
	 * Get the class and permission callback function based on the endpoint and method.
	 *
	 * @since  1.0.0
	 * @param  string  $endpoint  Name of the endpoint.
	 * @param  string  $method  HTTP method.
	 *
	 * @return  array  $callback
	 */
	public function getPermissionsClass($endpoint) {

		$classname = ucfirst($endpoint);
		$class = sprintf($this->config->getPsr() . '\%s', $classname);
		$method = 'permissions';

		$callback = array($class, $method);

		return $callback;
	}

	/**
	 * Get the arguments associated to a particular HTTP method im an endpoint.
	 *
	 * @since  1.0.0
	 * @param  string  $method  HTTP method.
	 *
	 * @return  '' or array of $args for a method key
	 */
	public function getArgs($method) {
    	
    	if($this->args) {
    		return $this->args[$method];
    	}

    	return '';
    }

    /**
	 * Insert method arguments in the class $args variable.
	 *
	 * @since  1.0.4
	 * @param  string  $method  HTTP method.
	 * @param  array  $args  Arguments to add with their respective attributes.
	 *
	 * @return  void
	 */
    public function addArgs(string $method, array $args) {

    	if(!$this->args[$method]) {
			$this->args[$method] = $args;
		} else {
			array_push($this->args[$method], $args);
		}
	}

	/**
	 * Autoloads the generated controller classes.
	 *
	 * @since  1.0.0
	 * @return  void
	 */
	public function autoload() {
		
		$autoload = new AL();
		$autoload->addDirectory($this->path);
		$autoload->setTempDirectory(__DIR__ . '/temp');
		$autoload->register();
	}
	/**
	 * Helpers in combining multidimensional arrays for different methods.
	 *
	 * @since  1.0.2
	 * @param  array  $a  First array
	 * @param  array  $b  Second array
	 *
	 * @return  array_combine()
	 */
    public function array_adjust($a, $b) {
    	
    	$acount = count($a);
    	$bcount = count($b);
    	$size = ($acount > $bcount) ? $bcount : $acount;
    	$a = array_slice($a, 0, $size);
    	$b = array_slice($b, 0, $size);
    	
    	return array_combine($a, $b);
	}
}