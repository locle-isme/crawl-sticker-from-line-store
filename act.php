<?php
header('content-type: application/json');
include 'simplehtmldom_1_9_1/simple_html_dom.php';
$response = [];
$link = 'https://store.line.me/stickershop/product/13974331/en';
$url_sticker = htmlspecialchars_decode($link);
$matches = check_URL($url_sticker);
//print_r($matches);
if ($matches) {
    $id = $matches[1];
    $path = 'uploads/' . $id;
    $zip_name = $path . ".zip";
    $html = file_get_html($url_sticker);
    $images = $html->find('.FnImage');
    $my_zip = new ZipArchive();
    create_folder($path);
    if (file_exists($zip_name)) {
        $response = [
            'status_code' => 400,
            'message' => 'File already on system, continue?',
            'type' => 'error'
        ];
    } else if ($my_zip->open($zip_name, ZipArchive::CREATE) !== true) {
        $response = [
            'status_code' => 500,
            'message' => 'Can not create zip file. Try again later!',
            'type' => 'error'
        ];
    } else {
        foreach ($images as $key => $image) {
            if (isset($image->find('span', 0)->style)) {
                $style = $image->find('span', 0)->style;
                $url = find_URL($style);
                $image_name = $path . '/' . $key . '.png';
                file_put_contents($image_name, file_get_contents($url));
                resize_image($image_name, 75, 75);
                $my_zip->addFile($image_name);
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

    remove_folder($path);
} else {
    $response = [
        'status_code' => 400,
        'message' => 'URL not valid!',
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
    $flag = preg_match("/product\/([a-z0-9]+)\/?en/", $url, $matches);
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

