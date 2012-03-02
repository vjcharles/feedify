README
======

What this does: Renders an iTunes compatible rss feed with any compatible files in the local directory

Why? To make it extremely simple to create a new podcast episode.

## Setup
* copy the contents of the uploads directory into the directory you want to feedify

* it's assumed the files are in the same directory as xml.php
* copy rss_config_sample.yml and configure with your details and defaults
  http://www.apple.com/itunes/podcasts/specs.html <--helpful
* upload a compatible media file to this directory
* look at the page: ...xml.php
* test it at http://www.feedvalidator.org
* submit it to whoever and iTunes

## Platforms tested
Tested on a shared dreamhost unix server running php 5.3.5 and os x php 5.3.8

## Note to possible contributors and other random thoughts
This idea started after looking for a final project that already does this. I found some sample code in a forum response that did a basic xml version, but I wanted it to be itunes compatible and a little more...robust: http://www.codingforums.com/showthread.php?t=155776

Why PHP? Because it's running on the server I wanted to use this on, and it seemed like the easiest way to make this happen right away.

The real work was the tedium of making it iTunes compatible and configurable via my preferred config format, yaml. I used mustache for the xml 'view' to see what it's like using mustache in a new context.

There are probably numerous style and convention issues. Feel free to update that sort of thing. Just send a pull request. This is my first php project

## Disclaimer / software license
IDGAF aka MIT or something
Use it however you want and don't blame me if anything bad happens

Oh, but look and listen to the licenses in the lib directory

## Features that would be nice
* mp3 and wav files found and loaded, other places
* better default values filled for itunes podcast, like filling in defaults
* if a text file exists with the same name as the file with the extension the same name as the mustache tag, that value is used. Example: songName.summary.tag
  or songName.yml whatever is in that will be used for that song.
  a better idea is to probably just add better metadata to each file
* cache it, reload if filec ount is > existing. may not help performance tho.
  there is a known issue if you hit this site when a file is being uploaded, iTunes will download the partial file and cache that, making it seem like the track is incomplete. Not sure a work-around other than re-subscribing to the feed, but that's not a good choice.
*  url get rid of full paths. the script should find the url based on the context of the file
* rss_style.css could be fixed up and added. Currently it's just sitting there unused
