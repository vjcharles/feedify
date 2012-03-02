<?php
//phpinfo();
function pr($var) { print '<pre>'; print_r($var); print '</pre>'; }

include('feedify/lib/Mustache.php');
include('feedify/lib/sfYamlParser.php');
require_once('feedify/lib/getid3/getid3.php');

$myfeed = new RSSFeed();

// Output the XML File, could write it to file too
echo $myfeed->output();

//Utilities
function getMediaType($ext) {
    switch ($ext) {
    case 'mp3':
        return 'audio/mpeg';
    case 'm4a':
        return 'audio/x-m4a';
    case 'mp4':
        return 'video/mp4';
    case 'm4v':
        return 'video/x-m4v';
    case 'mov':
        return 'video/quicktime';
    case 'pdf':
        return 'application/pdf';
    case 'epub':
        return 'document/x-epub';
    case 'wav':
        return 'audio/wav';
    default:
        return '';
    }
}

function isValidFiletype($filename, $array) {
    foreach ($array as $ext) {
        if (strpos($filename, '.'.$ext, 1)) {
            return true;
        }
    }
    return false;
}
function curPageURL() {
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
       $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}
function curDirURL() {
    $pageURL = curPageURL();
    return dirname($pageURL);
}

class RSSFeed {
    // VARIABLES
    var $files = array();
    var $nritems=0;
    var $template_channel_path='';
    var $template_item_path='';
    var $config_data = array();
    var $item_defaults = array();


    // FUNCTIONS
    // constructor
    function RSSFeed() {
        $this->template_channel_path='';
        $this->template_item_path='';
        $this->initConfigData();
        $this->getFiles();
        $this->initItems();
    }
    function getFiles() {
        $files = array();
        $dir = new DirectoryIterator('.');
        foreach ($dir as $fileinfo) {
          if (isValidFiletype($fileinfo->getFilename(), $this->config_data['filetypes_to_feedify'] )) {
            //if modified date is same, makes sure there are no duplicate keys
            $uniquifier = 0;
            $key = $fileinfo->getMTime().$uniquifier;
            while (isset($files[$fileinfo->getMTime().$uniquifier])) {
              $uniquifier = $uniquifier + 1;
              $key = $fileinfo->getMTime().$uniquifier;
            }
            $somethin = clone $fileinfo;
            $files[$key] = $somethin;
          }
        }
        krsort($files);
        $this->files = $files;
    }

    //get mustache values from config yaml file
    function initConfigData() {
        $yaml = new sfYamlParser();
        $this->config_data = $yaml->parse(file_get_contents('feedify/rss_config.yml'));
        date_default_timezone_set($this->config_data['default_timezone']);
        $this->item_defaults = $this->config_data['item'];
        $this->config_data['item'] = null;
        $this->config_data['channel']['lastBuildDate'] = date('r');
        $this->config_data['channel']['this_year'] = date('Y');
    }
    //set items
    function initItems() {
        date_default_timezone_set($this->config_data['default_timezone']);
        $this->config_data['items'] = array();
        foreach ($this->files as $file) {
            $this->config_data['items'][$this->nritems] = $this->item_defaults;
            $this->config_data['items'][$this->nritems]['pubDate'] = date('r', $file->getMTime());
            $this->config_data['items'][$this->nritems]['description'] = $file->getFilename();
            $this->config_data['items'][$this->nritems]['content_encoded'] = $file->getFilename();
            //$file_url = $_SERVER['HTTP_REFERER'] . '/uploads/' . $file->getFilename();
            $file_url = curDirURL().'/'.$file->getFilename();
            $this->config_data['items'][$this->nritems]['guid'] = $file_url;
            $this->config_data['items'][$this->nritems]['link'] = $file_url;
            $this->config_data['items'][$this->nritems]['enclosure']['url'] = $file_url;
            $this->config_data['items'][$this->nritems]['enclosure']['file_size'] = $file->getSize();
            //$file_extension = $file->getExtension();//requires php 5.3.6+
            $file_extension = end(explode('.', $file->getFilename()));
            $this->config_data['items'][$this->nritems]['enclosure']['type'] = getMediaType($file_extension);

            //getID3 meta info
            $getID3 = new getID3;
            $fileinformation = $getID3->analyze($file->getFilename());
            getid3_lib::CopyTagsToComments($fileinformation);

            if (isset($fileinformation['comments']['artist'])) {
                $this->config_data['items'][$this->nritems]['itunes_author'] = implode($fileinformation['comments']['artist']);
            }
            if (isset($fileinformation['comments']['title'])) {
                $title = implode($fileinformation['comments']['title']);
                $this->config_data['items'][$this->nritems]['title'] = $title;
                $this->config_data['items'][$this->nritems]['itunes_subtitle'] = $title;
                $this->config_data['items'][$this->nritems]['itunes_summary'] = $title;
            } else {
                $filename = $file->getFilename();
                $this->config_data['items'][$this->nritems]['title'] = $filename;
                $this->config_data['items'][$this->nritems]['itunes_subtitle'] = $filename;
                $this->config_data['items'][$this->nritems]['itunes_summary'] = $filename;
            }

            $this->config_data['items'][$this->nritems]['itunes_duration'] = $fileinformation['playtime_string'];
            $this->nritems++;
        }
   }

    function output() {
        $fileOfMustache = file_get_contents($this->config_data['template_channel_path']);
        $m = new Mustache;
        $output = header("Content-type: text/xml") . $m->render($fileOfMustache, $this->config_data);
        return $output;
    }
};
?>
