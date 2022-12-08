<?php

class DOMEvent {
	
	public $bubbles = true;
	
	public $cancelable = true;
	
	public $currentTarget;
	
	public $detail;	
	
	public $eventPhase;	
	
	public $explicitOriginalTarget; 
	
	public $originalTarget;	
	
	public $relatedTarget;
	
	public $target;
	
	public $timeStamp;
	
	public $type;
	public $runDefault = true;
	public $data = null;
	public function __construct($data) {
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
		if (! $this->timeStamp)
			$this->timeStamp = time();
	}
	
	public function preventDefault() {
		$this->runDefault = false;
	}
	
	public function stopPropagation() {
		$this->bubbles = false;
	}
}
