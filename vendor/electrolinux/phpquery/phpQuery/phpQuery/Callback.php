<?php
interface ICallbackNamed {
	function hasName();
	function getName();
}
class Callback
	implements ICallbackNamed {
	public $callback = null;
	public $params = null;
	protected $name;
	public function __construct($callback, $param1 = null, $param2 = null, 
			$param3 = null) {
		$params = func_get_args();
		$params = array_slice($params, 1);
		if ($callback instanceof Callback) {
		} else {
			$this->callback = $callback;
			$this->params = $params;
		}
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
//	public function addParams() {
//		$params = func_get_args();
//		return new Callback($this->callback, $this->params+$params);
//	}
}
class CallbackBody extends Callback {
	public function __construct($paramList, $code, $param1 = null, $param2 = null, 
			$param3 = null) {
		$params = func_get_args();
		$params = array_slice($params, 2);
		$this->callback = create_function($paramList, $code);
		$this->params = $params;
	}
}
class CallbackReturnReference extends Callback
	implements ICallbackNamed {
	protected $reference;
	public function __construct(&$reference, $name = null){
		$this->reference =& $reference;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->reference;
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
}
class CallbackReturnValue extends Callback
	implements ICallbackNamed {
	protected $value;
	protected $name;
	public function __construct($value, $name = null){
		$this->value =& $value;
		$this->name = $name;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->value;
	}
	public function __toString() {
		return $this->getName();
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
}
class CallbackParameterToReference extends Callback {
	public function __construct(&$reference){
		$this->callback =& $reference;
	}
}
//class CallbackReference extends Callback {
//	
//	public function __construct(&$reference, $name = null){
//		$this->callback =& $reference;
//	}
//}
class CallbackParam {}