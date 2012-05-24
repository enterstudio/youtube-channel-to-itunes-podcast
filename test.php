<?php
$youtube_url = "http://www.youtube.com/watch?v=eeRXN-t2aRg";
$quality = 18;
$video_name = "test2.mp4";
exec ("./lib/youtube.sh $youtube_url -f $quality -o $video_name > test.log", $response, $result);

$output = file("test.log");
echo "<pre>";
echo($output[5]);
echo "</pre>";

?>