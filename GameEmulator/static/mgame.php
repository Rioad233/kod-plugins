<?php
$webHost = "http://static.tenfell.cn/kodbox/game-emulator-web";
$handle = fopen($webHost."/mgame.html", "rb");
$contents = "";
do {
    $data = fread($handle, 8192);
    if (strlen($data) == 0) {
        break;
    }
    $contents .= $data;
} while(true);
fclose ($handle);
$source = "./asserts/js/init.js";
$target = $webHost."/asserts/js/init.js";
echo str_replace($source,$target,$contents);
echo "<script src='./ext.js'></script>";
