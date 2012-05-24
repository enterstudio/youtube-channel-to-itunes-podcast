<?php
//view-source:http://localhost/~russoedu/youtube/?channel=jovemnerd&label=JovemNerd
// http://localhost/~russoedu/youtube/?channel=OficialMundoCanibal&label=MundoCanibal
if(!(isset($_GET["channel"]))){
	echo "<html><body>Invalid parameters. Please, add \"channel\" (YouTube channel name) parameter.</br>".
	"You can also add \"search\" and \"label\" parameters.</br>".
	"Example: http://localhost$_SERVER[REQUEST_URI]?channel=jovemnerd&label=JovemNerd&search=nerdplayer</br>".
	"Access <a href=\"http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs\" target=\"_blank\"> this Wikipedia Article </a>, to check qualities (default is 18).</html>";
}
else{
	//Check if youtube is responding
	if (fsockopen("www.youtube.com", 80)){
		include('util.php');
		
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
		
		$logger->debug("\n\nYouTube Feed: $you_tube_feed\n$limit videos is the LIMIT\n" . count($youtube_feed_xml->channel->item) . " videos returned in feed \n");
		
		foreach ($youtube_feed_xml->channel->item as $item){
			$logger->debug("$counter | Title: $item->title");
			if ($counter++ >= $limit)
				break;
			$content = $item->children('http://purl.org/rss/1.0/modules/content/');
			$media = $item->children('http://search.yahoo.com/mrss/');
			$video_state = get_video_state($item->title, $videos_xml_path);
			$link;
			//Video hasn't be downloaded
			if ($video_state == false){
				$logger->debug("Video in \"NULL\" state. New Video detected.");
				$video_time = convert_video_duration($media->group->content->attributes());
				$youtube_url = get_youtube_url($media->group->content->attributes());
				$id = add_new_video($item->title, $youtube_url, $item->description, $video_time, $item->pubDate, $item->author, $podcast_label, $videos_xml_path);
				$video_name = "$videos_path$podcast_label/$id$video_extension";
				download_video($youtube_url, $video_quality, $video_name);
				$video_state = 1;
			}
			//Video has been downloaded and state is "delete"-> Advance video state to ignore and delete the file
			elseif($video_state == "delete"){
				$logger->debug("Video in \"delete\" state");
				advance_video_state($item->title, $videos_xml_path);
				delete_ignored_video($item->title, $videos_path, $videos_xml_path, $video_extension);
				$logger->debug("");
			}
			// Video is in ignore state -> do't show video in XML
			elseif($video_state == "ignore"){
				$logger->debug("Video in \"ignore\" state");
				$logger->debug("");
			}
			//Video has been downloaded and state > 0 -> Advance state and show video URL
			if($video_state > 0){
				$logger->debug("Video in \"numbered\" state (1 to 6)");
				advance_video_state($item->title, $videos_xml_path);
				if (file_has_been_downloaded($item->title, $videos_path, $videos_xml_path, $video_extension)){
					$logger->debug("File download finished correctly");
					// Reload the XML in case a new video has been inserted
					$videos_xml = simplexml_load_file($videos_xml_path);
					echo get_video($item->title, $url_path, $video_type, $video_extension, $videos_xml);
					$logger->debug("");
				}
				else{
					$logger->debug("File has NOT been downloaded. Stop the loop");
					$counter = $limit;
					$logger->debug("");
				}
			}
		}
		$logger->debug("END OF FILE\n\n");
		echo "	</channel>\n</rss>";
	}
	else{
		return null;
	}
}
?>