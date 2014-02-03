<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * class for private auth
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Host {
	
	/**
	 * Get current host
	 *
	 * @param array $options
	 * @return string
	 */
	public static function get(){
		return Arr::get($_SERVER, 'HTTP_HOST');
	}
}