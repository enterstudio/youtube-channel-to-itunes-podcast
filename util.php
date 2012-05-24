<?php
include('lib/log.php');
/**
 * Converts video duration from seconds to "minutes:seconds" format
 * @param $content_attributes RSS content attributes ($media->group->content->attributes())
 * @return $mins:$secs String ("minutes:seconds")
 * @author Eduardo Russo
 **/
function convert_video_duration($content_attributes){
	global $logger;
	$seconds = $content_attributes["duration"];
	$mins = floor ($seconds / 60);
	$secs = $seconds % 60;
	if ($secs < 10)
		$secs = "0" . $secs;
	$logger->info("Converted $seconds seconds to $mins:$secs");
	return "$mins:$secs";
}
/**
 * Creates the begining of the iTunes XML Format with the channel attributes.
 * @param $podcast_label The label of the podcast
 * @param $xml_channel The Youtube feed channel ($xml->channel)
 * @return String (Channel XML)
 * @author Eduardo Russo
 **/
function create_channel($podcast_label, $xml_channel){
	global $logger;
	$logger->info("Creating Podcast Channel");
	
	$channel = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$channel .= "<rss xmlns:itunes=\"http://www.itunes.com/dtds/podcast-1.0.dtd\" version=\"2.0\">\n";
	$channel .= "	<channel>\n";
	$channel .= "		<title>$podcast_label</title>\n";
	$channel .= "		<description>This is the Podcast generated from YouTube: $podcast_label</description>\n";
	$channel .= "		" . $xml_channel->link->asXML() . "\n";
	$channel .= "		<copyright>2012, Eduardo Russo</copyright>\n";
	$channel .= "		<language>en-us</language>\n";
	$channel .= "		" . $xml_channel->lastBuildDate->asXML() . "\n";
	$channel .= "		<managingEditor>jovemnerd@gmail.com</managingEditor>\n";
	$channel .= "		<pubDate>" . $xml_channel->lastBuildDate . "</pubDate>\n";
	$channel .= "		<generator>PHP YouTube Podcast Generator by Eduardo Russo</generator>\n";
	$channel .= "		<itunes:subtitle>This is the Podcast generated from YouTube: $podcast_label</itunes:subtitle>\n";
	$channel .= "		<itunes:summary>This is the Podcast generated from YouTube: $podcast_label</itunes:summary>\n";
	$channel .= "		<itunes:category text=\"TV &amp; Film\"></itunes:category>\n";
	$channel .= "		<itunes:keywords>YouTube, video, $xml_channel->managingEditor</itunes:keywords>\n";
	$channel .= "		<itunes:author>$xml_channel->managingEditor</itunes:author>\n";
	$channel .= "		<itunes:owner>\n";
	$channel .= "			<itunes:email>russoedu@gmail.com</itunes:email>\n";
	$channel .= "			<itunes:name>Eduardo Russo</itunes:name>\n";
	$channel .= "		</itunes:owner>\n";
	$channel .= "		<itunes:image href=\"" . $xml_channel->image->url . "\" />\n";
	$channel .= "		<itunes:explicit>no</itunes:explicit>\n";
	echo $channel;
}

/**
 * Download a YouTube video from it's URL using ./youtube-dl.sh
 * @param $youtube_url The YouTube video URL in the format "http://www.youtube.com/watch?v=video_id"
 * @param $quality The youtube video quality (http://en.wikipedia.org/wiki/YouTube#Quality_and_codecs)
 * @param $video_name The full path and name of the video to be saved
 * @author Eduardo Russo
 **/
function download_video($youtube_url, $quality, $video_name, $video_id){
	global $logger;
	$logger->info("Downloading video using command: ./lib/youtube-dl-russo.sh $youtube_url -f $quality -o $video_name > logs/download_$video_id.log");
	exec ("./lib/youtube-dl-russo.sh $youtube_url -f $quality -o $video_name > logs/download_$video_id.log");
}

/**
 * Get the YouTube URL from a certain YouTube video
 * @param $content_attributes The RSS content attributes ($media->group->content->attributes())
 * @return $video_url String
 * @author Eduardo Russo
 **/
function get_youtube_url($content_attributes){
	global $logger;
	$video_url = explode("v/", $content_attributes["url"]);
	$video_url = explode("?", $video_url[1]);
	$video_url = "http://www.youtube.com/watch?v=" . $video_url[0];
	$logger->info("YouTube video URL: $video_url");
	return $video_url;
}

/**
 * Get the video state of a video from it's title
 * @param $title The video title
 * @param $videos_xml_path The XML file path (videos.xml)
 * @return $video->state String or false if no video found
 * @author Eduardo Russo
 **/
function get_video_state($title, $videos_xml_path){
	global $logger;
	/* video states are:
		1 to 6 -> file may be downloaded, do not try to download again
		delete -> file should be in itunes. Delete it
		ignore -> file is not part of the desired podecast. Do not output it to the feed
	*/
	$xmldoc = simplexml_load_file($videos_xml_path);
    $videos = $xmldoc->videos;
	foreach($videos->video as $video){
		if (!strcmp($title, $video->title)){
			$logger->info("Video State: " . $video->state);
			return $video->state;
		}
	}
	$logger->info("No video found, it must be a new video");
	return false;
}

/**
 * Add a new video into videos.xml
 * @param $title The video title
 * @param $youtube_url The YouTube original URL
 * @param $description The video description
 * @param $video_time The video converted time
 * @param $pubDate The video publication date
 * @param $author The video author
 * @param $podcast_label The label of the Podcast
 * @param $videos_xml_path The XML file path (videos.xml)
 * @return $id The video ID
 * @author Eduardo Russo
 **/
function add_new_video($title, $youtube_url, $description, $video_time, $pubDate, $author, $podcast_label, $videos_xml_path){
	global $logger;
    $xmldoc = simplexml_load_file($videos_xml_path);
    $videos = $xmldoc->videos;
	$id = 0;
	foreach ($videos->video as $video){
		$id = $video->id;
	}
	$id = $id + 1;	
	$video = $videos->addChild("video");
	$video->addCHild("id", $id);
    $video->addCHild("state", "1");
	$video->addChild("label", $podcast_label);
    $video->addCHild("title", $title);
    $video->addCHild("youtubeUrl", $youtube_url);
    $video->addCHild("description", $description);
    $video->addCHild("time", $video_time);
    $video->addCHild("pubDate", $pubDate);
    $video->addCHild("author", $author);
    $xmldoc->asXML($videos_xml_path);

	$logger->info("Inserted video \"$title\"into \"videos.xml\" with ID = $id");
	return $id;
}

/**
 * Advance the state of a video into videos.xml, from 1 to "ignore"
 * @param $video_title The title of the video
 * @param $videos_xml_path The XML file path (videos.xml)
 * @author Eduardo Russo
 **/
function advance_video_state($video_title, $videos_xml_path){
	global $logger;
	$xmldoc = simplexml_load_file($videos_xml_path);
	$videos = $xmldoc->videos;
	$original_state;
	$final_state;
	foreach($videos->video as $video){
		if (!strcmp($video_title, $video->title)){
			$original_state = clone($video->state);
			if($video->state == 6){
				$video->state = "delete";
				$final_state = $video->state;
			}			
			elseif($video->state == "delete"){
				$video->state = "ignore";
				$final_state = $video->state;
			}
			else{
				$video->state = $video->state + 1;
				$final_state = $video->state;
			}
		}
	}
	$logger->info("Changed state from $original_state to $final_state");
	$xmldoc->asXML($videos_xml_path);
}

/**
 * Returns a video item.
 * @param $video_title The name of the video
 * @param $url_path The path to the video file
 * @param $video_type The video type ("video/mp4" is the default)
 * @param $video_extension The video extension (".mp4" is the default)
 * @param $xml The video XML object
 * @return String (Channel XML)
 * @author Eduardo Russo
 **/
function get_video($video_title, $url_path, $video_type, $video_extension, $xml){
	global $logger;
	$video_feed;
	foreach($xml->videos->video as $video){
		if (!strcmp($video_title, $video->title)){
			$link = "$url_path/files/" . $video->label . "/" . $video->id . $video_extension;
			
			$video_feed = "		<item>\n";
			$video_feed .= "			<title>$video->title</title>\n";
			$video_feed .= "			<description>$video->description</description>\n";
			$video_feed .= "			<link>$link</link>\n";
			$video_feed .= "			<enclosure url=\"$link\" length=\"10000\" type=\"$video_type\"></enclosure>\n";
			$video_feed .= "			<pubDate>$video->pubDate</pubDate>\n";
			$video_feed .= "			<itunes:subtitle>$video->description</itunes:subtitle>\n";
			$video_feed .= "			<itunes:summary>$video->description</itunes:summary>\n";
			$video_feed .= "			<itunes:duration>$video->time</itunes:duration>\n";
			$video_feed .= "			<itunes:author>$video->author</itunes:author>\n";
			$video_feed .= "			<itunes:explicit>no</itunes:explicit>\n";
			$video_feed .= "		</item>\n";
		}
	}
	$logger->info("Video <item> returned");
	return $video_feed;
}

/**
 * Get the base URL (http://your_url/other_paths)
 * @param $request_uri The requested URL
 * @return String with the complete URL to this directory
 * @author Eduardo Russo
 **/
function get_base_url($request_uri) {
	global $logger;
	$this_directory = dirname($request_uri);
	
	if (strpos($this_directory, "?") !== false)
		$this_directory = reset(explode("?", $this_directory));

	$logger->info("Base URL returned is: http://" . $_SERVER['HTTP_HOST'] . $this_directory);
	return "http://" . $_SERVER['HTTP_HOST'] . $this_directory;
}

/**
 * Deletes a video from the file and remove the entry from the xml
 * @param $video_title The video title
 * @param $videos_path The locations of the video file
 * @param $videos_xml_path The XML file path (videos.xml)
 * @param $video_extension The video extension
 * @author Eduardo Russo
 **/
function delete_ignored_video($video_title, $videos_path, $videos_xml_path, $video_extension){
	global $logger;
	$xmldoc = simplexml_load_file($videos_xml_path);
	$videos = $xmldoc->videos;
	foreach($videos->video as $video){
		if (!strcmp($video_title, $video->title)){
			$logger->info("Deleting video " . $videos_path . $video->label . "/" . $video->id . $video_extension);
			unlink($videos_path . $video->label . "/" . $video->id . $video_extension);
			unlink("logs/download_" . $video->id . ".log");
		}
	}
}

/**
 * Deletes a video from the file and remove the entry from the xml
 * @param $video_title The video title
 * @param $videos_path The locations of the video file
 * @param $videos_xml_path The XML file path (videos.xml)
 * @param $video_extension The video extension
 * @return void
 * @author Eduardo Russo
 **/
function file_has_been_downloaded($video_title, $videos_path, $videos_xml_path, $video_extension){
	global $logger;
	$logger->info("Checking if file has been downloaded");
	$xmldoc = simplexml_load_file($videos_xml_path);
	$videos = $xmldoc->videos;
	foreach($videos->video as $video){
		$video_name = $videos_path . $video->label . "/" . $video->id . $video_extension;
		if (!strcmp($video_title, $video->title)){
			//File has been completly downloaded
			if (file_exists($video_name)){
				$logger->info("File has been completly downloaded - return TRUE");
				return true;
			}
			//No file has been downloaded
			elseif (!file_exists ($video_name . ".part")){
				$logger->info("No file has been downloaded, not even \".part\" - return FALSE");
				$oNode = dom_import_simplexml($video);
				$oNode->parentNode->removeChild($oNode);
				$xmldoc->asXML($videos_xml_path);
				return false;
			}
			//Only part has been downloaded
			else{
				$logger->info("\"part\" has been downloaded");
				// echo "File is downloading";
				//If videoState = 6, delete the file and download again
				if($video->state >= 6){
					$logger->info("State is 6. Delete \"part\" file and try again on next load");
					unlink($video_name . ".part");
					$oNode = dom_import_simplexml($video);
					$oNode->parentNode->removeChild($oNode);
					$xmldoc->asXML($videos_xml_path);
				}
				else{
					$logger->info("State is less than 6. Waiting for file to finish the download");
				}
				$logger->info("return FALSE");
				return false;
			}
		}
	}
}
?>