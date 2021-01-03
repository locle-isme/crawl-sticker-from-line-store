<?php
header('content-type: application/json');
include 'simplehtmldom_1_9_1/simple_html_dom.php';
$response = [];
$data = json_decode(file_get_contents('php://input'), true); //get json params request
$url_sticker = isset($data['url']) ? htmlspecialchars_decode($data['url']) : "";
$is_continue = isset($data['is_continue']) ? boolval($data['is_continue']) : 0;
$size = isset($data['size']) ? info_size($data['size']) : -1;
$info_path = get_path($url_sticker);
if ($size == -1) {
    $response = [
        'status_code' => 400,
        'message' => 'Size not valid',
        'type' => 'error'
    ];
} else if ($info_path) {
    $id = $info_path['id'];
    $host = $info_path['host'];
    $path_save_tmp = 'uploads/' . $host . '-' . $id . '-' . $size;
    $zip_name = $path_save_tmp . ".zip";
    $my_zip = new ZipArchive();
    create_folder($path_save_tmp); //check folder and create folder
    $html = file_get_html($url_sticker);

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
        if ($host == "tlgrm.eu" && $is_continue == false) {
            $attr = "data-is-animated";
            $is_animated = (bool)$html->find('div#vue-sticker-pack', 0)->$attr;
            if ($is_animated) {
                $response = [
                    'status_code' => 101,
                    'message' => 'Album is animated, but we can process only images png. Do you wanna continue?',
                    'type' => 'error'
                ];
                goto json_encode;
            }
        }
        get_images($html, $my_zip, $host, $path_save_tmp, $size);

        $my_zip->close();
        $response = [
            'status_code' => 200,
            'message' => 'Get sticker success',
            'url' => $zip_name,
            'type' => 'success'
        ];
    }

    remove_folder($path_save_tmp); //remove folder and file in this

} else {
    $response = [
        'status_code' => 400,
        'message' => 'URL not valid',
        'type' => 'error'
    ];
}

json_encode:
echo json_encode($response);


function get_path($url)
{
    $result = [];
    $result['host'] = parse_url($url, PHP_URL_HOST);
    if (!$result['host']) return null;
    if ($result['host'] == "store.line.me") {
        if (!preg_match("/product\/([a-z0-9]+)\/{0,1}/", $url, $matches)) return null;
        $result['id'] = $matches[1];
        return $result;
    } else if ($result['host'] == "tlgrm.eu") {
        if (!preg_match("/stickers\/([A-Za-z0-9_]+)$/", $url, $matches)) return null;
        $result['id'] = $matches[1];
        return $result;
    } else {
        return null;
    }
}

function find_URL_image($str)
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

function get_images($html, &$my_zip, $host, $path, $size)
{
    if ($host == 'store.line.me') {
        get_images_line($html, $my_zip, $path, $size);
    } else if ($host == 'tlgrm.eu') {
        get_images_tlgrm($html, $my_zip, $path, $size);
    }
}

function get_images_tlgrm($html, &$my_zip, $path, $size)
{
    $url_image = $html->find('meta[property=og:image]', 0)->content; //get url first image
    $DOM_album = $html->find('div#vue-sticker-pack', 0);
    $v_count = "data-total";
    $count_image = $DOM_album->$v_count; //count quantity image of album
    if (isset($count_image, $url_image)) {
        for ($key = 1; $key <= $count_image; $key++) {
            $url = "https:" . preg_replace("/([a-z0-9A-Z]+)\.jpg$/", "$key.png", $url_image);
            $image_name = $path . '/' . $key . '.png';
            save_image($image_name, $url, $size, $my_zip);
        }
    }
}

function get_images_line($html, &$my_zip, $path, $size)
{
    $images = $html->find('.FnImage'); //get dom image
    foreach ($images as $key => $image) {
        if (isset($image->find('span', 0)->style)) {
            $style = $image->find('span', 0)->style;
            $url = find_URL_image($style); //get url image
            $image_name = $path . '/' . $key . '.png';
            save_image($image_name, $url, $size, $my_zip);
        }
    }
}

function save_image($image_name, $url, $size, &$my_zip)
{
    file_put_contents($image_name, file_get_contents($url)); //save file
    resize_image($image_name, $size, $size); //resize and re-save file
    $my_zip->addFile($image_name); //add file to zip
}