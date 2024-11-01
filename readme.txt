=== spYouTube ===
Contributors: SebastianK
Donate link: http://www.scriptpara.de/skripte/spyoutube/
Tags: youtube, upload, google, video, embed
Requires at least: 2.7
Tested up to: 2.8
Stable tag: 0.6

What this Plugin silhouetts against all the other YouTube plugins is the ability to upload 
videos directly from your administration panel. Further you can administrate all your videos 
at YouTube and implement them into your posts.

== Description ==

What this Plugin silhouetts against all the other YouTube plugins is the ability to upload 
videos directly from your administration panel. Further you can administrate all your videos 
at YouTube and implement them into your posts.

Forum: http://wordpress.org/support/topic/292145

Upcoming features: Any ideas ?? 

* sebastian-klaus@gmx.de
* http://www.scriptpara.de/skripte/spyoutube/

== Installation ==

**Precondition for spYouTube is PHP5 on your webserver!**

1. Get Zend Framework minimal from http://framework.zend.com/download/current/
2. Upload spYouTube to the /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Upload Zend Framewok to the /wp-content/plugins/spYouTube directory
5. Create a YouTube Account.(http://www.youtube.com/create_account) 
6. Create a Google Account to get the ClientID and a Developer Key. (http://code.google.com/apis/youtube/dashboard/)
5. Adjust the options

== Screenshots ==
 
1. Manage videos
2. Edit video
3. Upload new video
4. Settings
5. Insert video in post

== Frequently Asked Questions ==

**How to embed the videos**

Embed video: **[video id="XXX"]**
Optional parameter: **[video id="XXX" width="XXX" height="XXX" allowFullScreen="false" rating="true"]**

Embed all videos: **[allvideos]**
Optional parameter: **[allvideos template="XXX"]**

You can also embed your videos via the media-popup when you write a new post.
Be careful with searched videos! You only can add the rating to you own videos!

== Changelog ==

= 0.6 23.07.2009 =
* Direct YouTube Search in media popup added
* Video preview after search
* New language fi-Fi added
* Perfomance tuning

= 0.5 21.07.2009 =
* Star-rating added

= 0.4 20.07.2009 =
* Insert video in post

= 0.3 20.07.2009 =
* Bug in templating solved

= 0.2 17.07.2009 =
* initial release

== BuildIns ==

1. Zend Framework http://framework.zend.com/
2. JQuery http://www.jquery.com
2. Star-Rating http://www.fyneworks.com/ (modified)