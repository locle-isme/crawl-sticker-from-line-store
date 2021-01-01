<?php
header('content-type: application/json');
include 'simplehtmldom_1_9_1/simple_html_dom.php';
$response = [];
$data = json_decode(file_get_contents('php://input'), true); //get json params request
$url_sticker = isset($data['url']) ? htmlspecialchars_decode($data['url']) : "";
$is_continue = isset($data['is_continue']) ? boolval($data['is_continue']) : 0;
$size = isset($data['size']) ? info_size($data['size']) : -1;
//print_r($data);
//return;
$matches = check_URL($url_sticker);
if ($size == -1) {
    $response = [
        'status_code' => 400,
        'message' => 'Size not valid',
        'type' => 'error'
    ];
} else if ($matches) {
    $id = $matches[1];
    $path = 'uploads/' . $id . '-' . $size;
    $zip_name = $path . ".zip";
    $html = file_get_html($url_sticker);
    $images = $html->find('.FnImage'); //get dom image
    $my_zip = new ZipArchive();
    create_folder($path); //check folder and create folder
    if (file_exists($zip_name) && $is_continue == false) { //check exist file
        $response = [
            'status_code' => 100,
            'url' => $zip_name,
            'message' => 'File already on system, Do you wanna continue?',
            'type' => 'error'
        ];
    } else if ($my_zip->open($zip_name, ZipArchive::CREATE) !== true && $is_continue == false) {
        $response = [
            'status_code' => 500,
            'message' => 'Can not create zip file. Try again later!',
            'type' => 'error'
        ];
    } else {
        foreach ($images as $key => $image) {
            if (isset($image->find('span', 0)->style)) {
                $style = $image->find('span', 0)->style;
                $url = find_URL($style); //get url image
                $image_name = $path . '/' . $key . '.png';
                file_put_contents($image_name, file_get_contents($url)); //save file
                resize_image($image_name, $size, $size); //resize and re-save file
                $my_zip->addFile($image_name); //add file to zip
            }
        }

        $my_zip->close();
        $response = [
            'status_code' => 200,
            'message' => 'Get sticker success',
            'url' => $zip_name,
            'type' => 'success'
        ];
    }

    remove_folder($path); //remove folder and file in this

} else {
    $response = [
        'status_code' => 400,
        'message' => 'URL not valid',
        'type' => 'error'
    ];
}

echo json_encode($response);


function find_URL($str)
{
    preg_match("/https(.+).png/", $str, $matches);
    return isset($matches[0]) ? $matches[0] : null;
}

function create_folder($path)
{
    if (!file_exists($path)) {
        mkdir($path, 0777);
    }
}

function remove_folder($path)
{
    if (file_exists($path)) {
        $file_list = glob($path . '/*');
        foreach ($file_list as $file) {
            unlink($file);
        }
        rmdir($path);
    }
}

function check_URL($url)
{
    $flag = preg_match("/product\/([a-z0-9]+)\/{0,1}/", $url, $matches);
    return ($flag) ? $matches : false;
}

function resize_image($file, $w, $h, $crop = FALSE)
{
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width - ($width * abs($r - $w / $h)));
        } else {
            $height = ceil($height - ($height * abs($r - $w / $h)));
        }
        $new_width = $w;
        $new_height = $h;
    } else {
        if ($w / $h > $r) {
            $new_width = $h * $r;
            $new_height = $h;
        } else {
            $new_height = $w / $r;
            $new_width = $w;
        }
    }


    $src = imagecreatefrompng($file);
    $dst = imagecreatetruecolor($new_width, $new_height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    imagepng($dst, $file);
}


function info_size($type)
{
    $sizes = [50, 100, 150, 200, 250];
    return $type < count($sizes) ? $sizes[$type] : -1;
}