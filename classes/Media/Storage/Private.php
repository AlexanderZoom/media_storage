<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * class for private auth
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Private {
	
	/**
	 * Check auth for file
	 *
	 * @param array $options
	 * @return boolean
	 */
	public static function auth($options = array()){
		return false;
	}
}