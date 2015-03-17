This is a PHP Web Project that creates a iTunes Podcast feed in realtime from a YouTube Channel.

It uses youtube-dl.sh (http://rg3.github.com/youtube-dl/) to download the videos and returns a compatible iTunes feed.

To run this code, you'll need Apache with PHP enabled and Python (to run youtube-dl.sh)

In iTunes, add a podcast with the URL:

http://localhost/your_user_name/application_folder/?channel=youtube_channel&label=podcast_label

Example: http://localhost/~russoedu/youtube/?channel=eduardorusso&label=russo