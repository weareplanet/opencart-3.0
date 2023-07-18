<?php

namespace WeArePlanet\Entity;

/**
 *
 */
class VoidJob extends AbstractJob {

	protected static function getTableName(){
		return 'weareplanet_void_job';
	}
}