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

	protected $path;
	protected $version;
	/**
	 * Arguments to be implemented (optional). Use to apply all arguments to all methods - if looking for arguments applied individually to each method, use the function addArgs()
	 *
	 * @var array
	 */
	protected $args = '';

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
		$this->namespace = $this->config->getPsr();
		$this->path = $this->config->getPath();
		$this->version = $this->config->getVersion();

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

		if (!is_dir($this->path)) {
			mkdir($this->path, 0755, true);
		}

		if(!is_file($this->path . '/' . ucfirst($this->endpoint) . '.php')) {
			$class = new CT(ucfirst($this->endpoint));

			$class
				->addComment("Controller class for callbacks and permissions.\nRoute --> " . sprintf($this->config->getPsr() . '\%s', ucfirst($this->endpoint)))
				->addComment('@since ' . $this->config->getVersion());

			foreach ($method as $function) {
				$function = $class->addMethod(strtolower($function) . ucfirst($this->endpoint))
					->addComment('Handles ' . $function . ' requests to the endpoint.')
					->addComment('@return \WP_Rest_Response')
					->setBody('return new \WP_Rest_Response();');

				$function->addParameter('request') // $items = []          // &$items = []
					->setType('\WP_Rest_Request');
			}

			$permissions = $class->addMethod('permissions')
					->addComment('Authenticate or limitate requests to the endpoint.')
					->addComment('@return bool')
					->setBody("// Your conditions.\nreturn true;");

				$permissions->addParameter('request') // $items = []          // &$items = []
					->setType('\WP_Rest_Request');

			// to generate PHP code simply cast to string or use echo:
			$controller = fopen($this->config->getPath() . ucfirst($this->endpoint) . '.php', 'w+');
			fwrite($controller, "<?php\n\nnamespace " . $this->config->getPsr() . ";\n");
			fwrite($controller, $class);
			fclose($controller);

		}
	}

	public function getCallbackClass($endpoint, $method) {

		$classname = ucfirst($endpoint);
		$class = sprintf($this->config->getPsr() . '\%s', $classname);
		$function = strtolower($method) . $classname;

		$callback = array($class, $function);

		return $callback;
	}

	public function getPermissionsClass($endpoint) {

		$classname = ucfirst($endpoint);
		$class = sprintf($this->config->getPsr() . '\%s', $classname);
		$method = 'permissions';

		$callback = array($class, $method);

		return $callback;
	}

	public function getArgs($method) {
    	
    	if($this->args) {
    		return $this->args[$method];
    	}

    	return '';
    }

    public function addArgs(string $method, array $args) {

		$this->args[$method] = $args;
	}

	public function autoload() {
		
		$autoload = new AL();
		$autoload->addDirectory($this->path);
		$autoload->setTempDirectory(__DIR__ . '/temp');
		$autoload->register();
	}

    public function array_adjust($a, $b) {
    	
    	$acount = count($a);
    	$bcount = count($b);
    	$size = ($acount > $bcount) ? $bcount : $acount;
    	$a = array_slice($a, 0, $size);
    	$b = array_slice($b, 0, $size);
    	
    	return array_combine($a, $b);
	}
}