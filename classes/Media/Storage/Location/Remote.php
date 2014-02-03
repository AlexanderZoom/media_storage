<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Location_Remote extends Media_Storage_Location {
    public function __construct(Model_Media_Storage_Location $model){
        throw new Exception('Location Remote not implemented');
        parent::__construct($model);
    }
}