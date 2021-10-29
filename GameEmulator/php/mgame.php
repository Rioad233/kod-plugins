<?php
$webHost = "//static.tenfell.cn/kodbox/GameEmulatorWeb";
$handle = fopen("http:".$webHost."/mgame.html", "rb");
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
$target = "https:".$webHost."/asserts/js/init.js";
echo str_replace($source,$target,$contents);
