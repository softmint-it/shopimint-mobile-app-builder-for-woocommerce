<?php
class ShopimintUtils {
    static $folder_path = 'shopimint_config_files';
    static $old_folder_path = '2000/01';

    public static function create_json_folder(){
        $uploads_dir = wp_upload_dir();
        $folder = trailingslashit($uploads_dir["basedir"]) . ShopimintUtils::$folder_path;
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }
    }

    private static function get_folder_path($path){
        $uploads_dir = wp_upload_dir();
        $folder = trailingslashit($uploads_dir["basedir"]) . $path;
        return realpath($folder);
    }

    public static function get_json_folder(){
        return ShopimintUtils::get_folder_path(ShopimintUtils::$folder_path);
    }

    public static function get_old_json_folder(){
        return ShopimintUtils::get_folder_path(ShopimintUtils::$old_folder_path);
    }

    public static function get_json_file_url($file_name){
        $uploads_dir = wp_upload_dir();
        $p_path = ShopimintUtils::is_existed_old_file($file_name) ? ShopimintUtils::$old_folder_path : ShopimintUtils::$folder_path;
        $folder = trailingslashit($uploads_dir["baseurl"]) . $p_path;
        return trailingslashit($folder) . $file_name;
    }

    private static function is_existed_old_file($file_name){
        $old_path = ShopimintUtils::get_old_json_file_path($file_name);
        return file_exists($old_path);
    }

    public static function get_json_file_path($file_name){
        if(ShopimintUtils::is_existed_old_file($file_name)){
            return ShopimintUtils::get_old_json_file_path($file_name);
        }
        return trailingslashit(ShopimintUtils::get_json_folder()). $file_name;
    }

    private static function get_old_json_file_path($file_name){
        return trailingslashit(ShopimintUtils::get_old_json_folder()). $file_name;
    }

    public static function get_all_json_files(){
        $files = scandir(ShopimintUtils::get_json_folder());
        if(file_exists(ShopimintUtils::get_old_json_folder())){
            $old_files = scandir(ShopimintUtils::get_old_json_folder());
        }else{
            $old_files = [];
        }
        $configs = [];
        foreach (array_merge($old_files, $files) as $file) {
            if (strpos($file, "config") > -1 && strpos($file, ".json") > -1) {
                $configs[] = $file;
            }
        }
        return $configs;
    }

    public static function upload_file_by_admin($file_to_upload) {
        $file_name = $file_to_upload['name'];
        //validate file name
        preg_match('/config_[a-z]{2}.json/',$file_name, $output_array);
        if (count($output_array) == 0) {
            return 'You need to upload config_xx.json file';
        }else{
          $source      = $file_to_upload['tmp_name'];
          $fileContent = file_get_contents($source);
          $array = json_decode($fileContent, true);
          if($array){ //validate json file
            wp_upload_bits($file_name, null, file_get_contents($source)); 
            $destination = ShopimintUtils::get_json_file_path($file_name);
            ShopimintUtils::create_json_folder();
            move_uploaded_file($source, $destination);

            //delete old json file
            if(ShopimintUtils::is_existed_old_file($file_name)){
                unlink(ShopimintUtils::get_old_json_file_path($file_name));
            }
            return null;
          }else{
            return 'You need to upload config_xx.json file';
          }
        }
    }

    public static function delete_config_file($id, $nonce){
        if(strlen($id) == 2){
            if (wp_verify_nonce($nonce, 'delete_config_json_file')) {
                $filePath = ShopimintUtils::get_json_file_path("config_".$id.".json");
                unlink($filePath);
                echo "success";
                die();
            }
        }
    }

    public static function get_home_cache_path($lang){
        return trailingslashit(ShopimintUtils::get_json_folder()). "home_cache_".$lang.".json";
    }
}
?>