<?php defined('SYSPATH') OR die('No direct access allowed.');
//modules needed: utils(url,httprange) , i18nplural
return array
(
    'global' => array(
        
        //db options
        'category'   => array(), // all - empty array, or name of code media category for filter
        
        // file system options
        'folder_max' => 256, // folder max quantity
        'subfolder_max' => 4, // subfolder max quantity
        'file_max' => 3, // file max quantity
        'file_length' => 9, // length file name without extension
        'thumb_width' => '150', // width thumb for picture
        'thumb_height' => '150', // width thumb for picture
        
        // module options
        'reserved_size_time' => array(), // bytes => seconds
        'private_auth'       => 'Media_Storage_Private::auth',
    
        'secret_key' => 'fk49fiksldlkff34092-ut', // for remote type
        'current_host' => 'Media_Storage_Host::get',
    ),

    'location' => array(
        'main' => array //server code
        (
            'type'       => 'local',
            'host_id'   => '%www_host%', // must be equal value from global current_host (check hash access token on upload and private download)
            'data_type'  => array(Media_Storage_Location::DATA_TYPE_PUBLIC),
            'path'       => '%www_dir%/media',
            'url'        => '%www_url%/media', //path to the location for public url
            'url_private_download' => '%www_url%/media/download',
            'url_post_upload' => '%www_url%/media/upload',
            'url_post_upload_return' => 'json', //json or redirect
            'url_post_upload_return_redirect' => '%www_url%', //redirect url where params upload_file_name, upload_file_size, file_name, status OK | NOK , error, error_desc
            'nginx_support_download' => 0, //for nginx support download for private (X-Accel-Redirect) 0 is off, nginx location is on location /protected/ { internal; alias   /some/path/; # note the trailing slash}
            
            ///////// not used, for future
            'remote_type' => '', // http, ftp for type = remote
            
            'ftp_host'   => '',  //for ftp
            'ftp_user'   => '',  //for ftp
            'ftp_pass'   => '',  //for ftp
            
            
                        
        ),
    
        'private' => array //server code
        (
            'type'       => 'local',
            'host_id'   => '%www_host%', // must be equal value from global current_host (check hash access token on upload and private download)
            'data_type'  => array(Media_Storage_Location::DATA_TYPE_PRIVATE),
            'path'       => '%www_dir%/../private_media',
            'url'        => '%www_url%/media',
            'url_private_download' => '%www_url%/media/download',
            'url_post_upload' => '%www_url%/media/upload',
            'url_post_upload_return' => 'json', //json or redirect
            'url_post_upload_return_redirect' => '%www_url%', //redirect url where params upload_file_name, upload_file_size, file_name, status OK | NOK , error, error_desc
            'nginx_support_download' => '/private_media/', //for nginx support download for private (X-Accel-Redirect) 0 is off, nginx location is on location /protected/ { internal; alias   /some/path/; # note the trailing slash}
            
            ///////// not used, for future
            'remote_type' => '', // http, ftp for type = remote
            
            'ftp_host'   => '',  //for ftp
            'ftp_user'   => '',  //for ftp
            'ftp_pass'   => '',  //for ftp
            
        
        
        ),
    ),
);
