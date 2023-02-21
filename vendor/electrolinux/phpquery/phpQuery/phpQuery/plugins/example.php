<?php
abstract class phpQueryObjectPlugin_example {
	public static $phpQueryMethods = null;
	public static function example($self, $arg1) {
		$self->append('Im just an example !');
		return $self->find('div');
	}
	protected static function helperFunction() {
	}
}
abstract class phpQueryPlugin_example {
	public static $phpQueryMethods = null;
	public static function staticMethod() {
	}
}
?>