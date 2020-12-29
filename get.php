<?php
$result = "";
include 'simplehtmldom_1_9_1/simple_html_dom.php';
?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Crawl sticker Line</title>
        <link rel="stylesheet" href="assets/css/bootstrap.css">
    </head>
    <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar"
                        aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="">HOME</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="#">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                           aria-expanded="false">Dropdown <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <!--                            <li><a href="#">Action</a></li>-->
                            <!--                            <li><a href="#">Another action</a></li>-->
                            <!--                            <li><a href="#">Something else here</a></li>-->
                            <!--                            <li role="separator" class="divider"></li>-->
                            <!--                            <li class="dropdown-header">Nav header</li>-->
                            <!--                            <li><a href="#">Separated link</a></li>-->
                            <!--                            <li><a href="#">One more separated link</a></li>-->
                        </ul>
                    </li>
                </ul>
            </div><!--/.nav-collapse -->
        </div>
    </nav>

    <div class="container" style="padding-top: 100px">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Panel title</h3>
                    </div>
                    <div class="panel-body">
                        <form action="" method="post">
                            <div class="form-group has-warning">
                                <label class="control-label" for="inputWarning1">URL: </label>
                                <input name="URL" type="text" value="" class="form-control" id="inputWarning1"
                                       placeholder="Example: https://store.line.me/stickershop/product/13493978/en">
                            </div>

                            <button name="submit" class="btn btn-primary" type="submit">CRAW</button>
                        </form>


                        <?php
                        if (isset($_POST['submit'], $_POST['URL']) && empty($_POST['URL']) == false) :
                            $url_sticker = htmlspecialchars_decode($_POST['URL']);
                            $matches = checkURL($url_sticker);
                            echo $matches;
                            if ($matches):
                                $id = $matches[1];
                                $html = file_get_html($url_sticker);
                                $images = $html->find('.FnImage');
                                $path = 'images/' . $id;
                                checkFolder($path);
                                foreach ($images as $key => $image) {
                                    if (isset($image->find('span', 0)->style)) {
                                        $style = $image->find('span', 0)->style;
                                        $url = findURL($style);
                                        $imgName = $path . '/' . $key . '.png';
                                        file_put_contents($imgName, file_get_contents($url));
                                    }
                                }

                                echo "<script>alert('Done')</script>";
                                $result = $path;
                            else:
                                echo "<script>alert('Error'); window.location.assign('');</script>";
                            endif;


                        endif;

                        ?>

                        <?php if (empty($result) == false): ?>
                            <div class="form-group has-warning">
                                <label class="control-label" for="result">Result: </label>
                                <input name="result" type="text" class="form-control" id="result"
                                       value="<?= $result ?>">
                            </div>
                            <a class="btn btn-success" target="_blank" href="<?= $result ?>">Redirect...</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.js"></script>
    </body>
    </html>

<?php
function findURL($str)
{
    preg_match("/https(.+).png/", $str, $matches);
    return isset($matches[0]) ? $matches[0] : null;
}

function checkFolder($path)
{
    if (!file_exists($path)) {
        mkdir($path, 0777);
    }
}

function checkURL($url)
{
    $flag = preg_match("//", $url, $matches);
    return ($flag) ? $matches : false;
}

