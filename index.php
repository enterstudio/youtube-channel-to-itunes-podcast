<?php
if(!(isset($_GET["channel"]))){
	echo "<html><body>Invalid parameters. Please, add \"channel\" (YouTube channel name) parameter.</br>".
	"You can also add \"search\" and \"label\" parameters.</br>".
	"Example: http://localhost$_SERVER[REQUEST_URI]?channel=eduardorusso&label=russo&search=osx</br>".
	"Access <a href=\"http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs\" target=\"_blank\"> this Wikipedia Article </a>, to check qualities (default is 18).</html>";
}
else{
	// include('curl.php');
	// include('youtube.php');
	include('functions.php');

	//Fixed Params
	$you_tube_feed = "http://gdata.youtube.com/feeds/api/videos?alt=rss&orderby=published&author=" . $_GET["channel"];

	//http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs
	$video_quality = 18;
	$videos_path = "files/";
	$videos_xml_path = $videos_path . "videos.xml";
	$url_path = get_base_url($_SERVER['REQUEST_URI']);
	$limit = 50;
	$counter = 0;
	$video_type = "video/mp4";
	$video_extension = ".mp4";
	$podcast_label;
	
	//Feed optional parameters
	if(isset($_GET["search"]))
		$you_tube_feed .= "&vq=" . $_GET["search"];

	$you_tube_feed .= "&max-results=" . $limit;
	
	if(isset ($_GET["label"]))
		$podcast_label = str_replace(" ", "", $_GET["label"]);
	else
		$podcast_label = str_replace(" ", "", $_GET["channel"]);
		
	// Feeds and XMLs
	$youtube_feed_xml = simplexml_load_file($you_tube_feed);
	$videos_xml = simplexml_load_file($videos_xml_path);
	
	create_channel($podcast_label, $youtube_feed_xml->channel);

	foreach ($youtube_feed_xml->channel->item as $item){
		if ($counter++ >= $limit)
			break;
		$content = $item->children('http://purl.org/rss/1.0/modules/content/');
		$media = $item->children('http://search.yahoo.com/mrss/');
		$video_state = get_video_state($item->title, $videos_xml_path);
		$link;
		//Video hasn't be downloaded
		if ($video_state == false){
			$video_time = convert_video_duration($media->group->content->attributes());
			$youtube_url = get_youtube_url($media->group->content->attributes());
	
			$id = add_new_video($item->title, $youtube_url, $item->description, $video_time, $item->pubDate, $item->author, $podcast_label, $videos_xml_path);
			$video_name = "$videos_path$podcast_label/$id$video_extension";
			download_video($youtube_url, $video_quality, $video_name);
			// echo $link;
			// $video_name = "$podcast_label-$id$video_extension";
			// // echo "LINK original: \n$link\n\n\n";
			// 
			// $ch = curl_init("$url_path/download.php");
			// curl_setopt ($ch, CURLOPT_POST, 1);
			// curl_setopt ($ch, CURLOPT_POSTFIELDS, "url=$link&file=$video_name");
			// curl_exec ($ch);
			// curl_close ($ch);
		}
		//Video has been downloaded and state is "delete"-> Advance video state to ignore and delete the file
		elseif($video_state == "delete"){
			advance_video_state($item->title, $videos_xml_path);
			delete_ignored_video($item->title, $videos_path, $videos_xml_path, $video_extension);
		}
		//Video is in ignore state -> do't show video in XML
		elseif($video_state == "ignore"){
		}
		//Video has been downloaded and state > 0 -> Advance state and show video URL
		elseif($video_state > 0){
			advance_video_state($item->title, $videos_xml_path);
			if (file_has_been_downloaded($item->title, $videos_path, $videos_xml_path, $video_extension, $video_quality)){
				echo get_video($item->title, $url_path, $video_type, $video_extension, $videos_xml);
				echo "file has been downloaded";
			}
			else{
				echo "file has not been downloaded";
				$counter = $limit;
			}
		}
	}
	echo "	</channel>\n</rss>";
}
?>