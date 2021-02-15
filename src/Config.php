<?php

namespace WPH\Endpoints;

class Config {

	public $path = WP_CONTENT_DIR . '/endpoints';

	public $semVer = 'v1';

	public $psr = 'EndpointGroup';

	public function getPath() {
		return $this->path;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function getVersion() {
		return $this->semVer;
	}

	public function setVersion($semVer) {
		$this->semVer = $semVer;
	}

	public function getPsr() {
		return $this->psr;
	}

	public function setPsr($psr) {
		$this->psr = $psr;
	}

	public function getNamespace() {
		return $this->namespace;
	}

	public function setNamespace($namespace) {
		$this->namespace = $namespace;
	}
}