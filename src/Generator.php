<?php

namespace WPH\Endpoints;
use WPH\Endpoints\Config;

class Generator {

	protected $endpoint;
	protected $method;
	protected $namespace;
	protected $config;
	protected $args = '';

	public function __construct(string $namespace, string $endpoint, array $method, Config $config, $args = null) {

		$this->namespace = $namespace;
		$this->endpoint = $endpoint;
		$this->method = $method;
		$this->config = $config;

		if(!is_null($args)) {
			$this->args = $this->array_adjust($method, $args);
		}

		if(!is_file(SHIPSMART_ROUTES . ucfirst($endpoint) . '.php')) {
			$class = new \Nette\PhpGenerator\ClassType(ucfirst($endpoint));

			$class
				->addComment("Controller class for callbacks and permissions.\nRoute --> " . sprintf($this->config->getPsr() . '\%s', ucfirst($endpoint)))
				->addComment('@since ' . $this->config->getVersion());

			foreach ($method as $function) {
				$function = $class->addMethod(strtolower($function) . ucfirst($endpoint))
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
			$controller = fopen($this->config->getPath() . ucfirst($endpoint) . '.php', 'w+');
			fwrite($controller, "<?php\n\nnamespace " . $this->config->getPsr() . ";\n");
			fwrite($controller, $class);
			fclose($controller);

		}

		add_action('rest_api_init', array($this, 'route'));
	}

	public function route() {

		//$other = new Shipsmart\Routes\Boxes();

		foreach ($this->method as $httpMethod) {
			register_rest_route(
				$this->namespace . $this::SHIPSMART_API_VERSION,
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

	public function teste() {
		return 'Teste';
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

    public function array_adjust($a, $b) {
    	
    	$acount = count($a);
    	$bcount = count($b);
    	$size = ($acount > $bcount) ? $bcount : $acount;
    	$a = array_slice($a, 0, $size);
    	$b = array_slice($b, 0, $size);
    	
    	return array_combine($a, $b);
	}
}