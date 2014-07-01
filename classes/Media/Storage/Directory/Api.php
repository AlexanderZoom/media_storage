<?php defined('SYSPATH') OR die('No direct script access.');
/**
 *
 *
 * @package    PegasLab/Media
 * @author     PegasLab
 * @copyright  (c) 2013 PegasLab
 * @license    http://pegaslab.com/license
 */
class Media_Storage_Directory_Api extends Media_Storage_Directory {
    public function __construct(Media_Storage_Location $location){
        throw new Exception('Ftp Directory not implemented');
        parent::__construct($location);
    }
    
    public function init(){
        $this->_fileObject = new Media_Storage_File_Api($this);
    }
}