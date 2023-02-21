<?php
$self = $self;
$self
	->find('script')
		->add('meta[http-equiv=refresh]')
			->add('meta[http-equiv=Refresh]')
				->remove();