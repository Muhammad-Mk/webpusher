<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function url_match($site_url, $requested_server_url){
    $site_url = (strpos($site_url, 'https://www.') !== false) ? str_replace("https://www.","",$site_url) : str_replace("https://","",$site_url);
    $requested_server_url = (strpos($requested_server_url, 'https://www.') !== false) ? str_replace("https://www.","",$requested_server_url) : str_replace("https://","",$requested_server_url);
    // echo "==> site url: " . $site_url . ", requested_server_url: " . $requested_server_url . "<br><br>";
    if($site_url != $requested_server_url){
        return false;
    }
    return true;
}

function isPathExist(){
    $folder_path = "subscribers/custom-prompt";
    if(!is_dir($path)) {
        mkdir($path, 0777, TRUE);
    }
}