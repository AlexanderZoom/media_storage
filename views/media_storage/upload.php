<?php defined('SYSPATH') or die('No direct script access.');
if ($error){
    $arr = array(
    'status' => $status,
    'error' => ___($error),
    'error_code' => $error_code
    );
    if (isset($debug)){
        $arr['debug'] = $debug;
    }
    echo json_encode($arr);
}
else {
    echo json_encode(array(
        'status' => $status,
        'file_id' => $file_id,
        'file_name' => $file_name,
        'file_size' => $file_size,
        'file_type' => $file_type,
        'file_private' => $file_private,
        'file_url'     => $file_url
    ));
}
?>
