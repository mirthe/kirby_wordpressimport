<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/*
 * Functionality to convert Wordpress a export to something closer to the Kirby structure,
 * in order to help Paul Swain's specific migration needs.
 * Uses HTML to Markdown functionality by Nick Cernis - https://github.com/nickcernis/html-to-markdown
 * Added on by Mirthe Valentijn for my site Mirthe.org
 */

require_once('HTML_To_Markdown.php');
// require_once('sanitizeFileName.php');

//Define the namespaces used in the XML document
$ns = array (
    'excerpt' => "http://wordpress.org/export/1.2/excerpt/",
    'content' => "http://purl.org/rss/1.0/modules/content/",
    'wfw' => "http://wellformedweb.org/CommentAPI/",
    'dc' => "http://purl.org/dc/elements/1.1/",
    'wp' => "http://wordpress.org/export/1.2/"
);
 
//Get the contents of the import file
$importfile = 'update_live.xml';
$exportdir = 'blog/'; //Include training slash please
$xml = file_get_contents($importfile);
$xml = new SimpleXmlElement($xml);

// print_r($xml);
// exit();

$dutchReplaceMap = [
    'ä' => 'a',
    'Ä' => 'A',
    'á' => 'a',
    'à' => 'a',
    'å' => 'a',
    'Á' => 'A',
    'ü' => 'u',
    'û' => 'u',
    'ú' => 'u',
    'Ü' => 'U',
    'ö' => 'o',
    'Ö' => 'O',
    'Ó' => 'O',
    'ó' => 'o',
    'ò' => 'o',
    'ō' => 'o',
    'ø' => 'o',
    'Ø' => 'O',
    'ß' => 'ss',
    'é' => 'e',
    'è' => 'e',
    'ë' => 'e',
    'Ë' => 'e',
    'ð' => 'th',
    'ý' => 'y',
    'í' => 'i',
    'ñ' => 'n'
];

//Grab each item
foreach ($xml->channel->item as $item)
{
    $article = array();
    $article['title'] = $item->title;
    $article['link'] = $item->link;
    $article['fullDate'] = $item->pubDate;
    $article['pubDate'] = date('Y/m/d', strtotime($item->pubDate));    
    $article['timestamp'] = strtotime($item->pubDate);
    $article['description'] = (string) trim($item->description);
    $article['status'] = (string) trim($item->status);
    $article['thumbnail'] = (string) trim($item->thumbnail);

    //Get the category and tags for each post
    $tags = array();
    $categories = array();
    foreach ($item->category as $cat) {
        $cattype = $cat['domain'];
        
        if($cattype == "post_tag") { //Tags
            array_push($tags,$cat);
        }
        elseif($cattype == "category") { //Category
            array_push($categories,$cat);
        }
    }

    //Get data held in namespaces
    $content = $item->children($ns['content']);
    $wfw     = $item->children($ns['wfw']);

    $article['content'] = (string)trim($content->encoded);
    $article['content'] = mb_convert_encoding($article['content'], 'HTML-ENTITIES', "UTF-8");

    $article['content'] = str_replace('[flickr_tags tags="', "(photogrid: ", $article['content']);
    $article['content'] = str_replace('" tags_mode="all"]', ")", $article['content']);

    $morestring = '<!--more-->';
    $explode_content = explode( $morestring, $article['content'] );

    // dit ook als markdown doen? of in 1x en dan splitten
    $content_summary = $explode_content[0];
    if (count($explode_content) > 1)
        {$content_moretext = $explode_content[1];}
    else {$content_moretext = "";}

    //Convert to markdown - optional param to strip tags
    $markdown = new HTML_To_Markdown($article['content'], array('strip_tags' => true));
    
    //Addition for conversion - strip Wordpress shortcodes for captions
    $markdown = preg_replace("/\[caption(.*?)\]/", "", $markdown);
    $markdown = preg_replace("/\[\/caption\]/", "", $markdown);

    // vreemde tekens uit URL halen, zie bij http://localhost:8882/mirthe_kirby/blog/2015/01/Skagaströnd
    // https://blog.liplex.de/generate-file-name-including-german-umlauts-in-php/
    // $cleantitle = new sanitizeFileName($titelalsstring);  doesn't work, I'll do it inline for now
    $titelalsstring = $article['title'];
    $titelalsstring = preg_replace('/\s+/', ' ', $titelalsstring);
    $titelalsstring = preg_replace('/\s/', '-', $titelalsstring);
    $titelalsstring = str_replace(array_keys($dutchReplaceMap), $dutchReplaceMap, $titelalsstring);
    $titelalsstring = preg_replace("([^\w\s\d\-])", '', $titelalsstring);
    $titelalsstring = strtolower($titelalsstring);
    $cleantitle = preg_replace('/-+/', '-', $titelalsstring);

    $tmpdate = str_replace('/', '', $article['pubDate']); //You don't want slashes, or it'll look for directories
    
    setlocale(LC_ALL, 'nl_NL');

    $jaar = date('Y', $article['timestamp']);
    $maand = date('m', $article['timestamp']);
    $maanddisplay = ucfirst(strftime('%B', $article['timestamp']));
    
    // folders aanmaken
    if (!is_dir($exportdir . $jaar)) {
        mkdir($exportdir . $jaar);
        file_put_contents($exportdir . $jaar. '/blog_year.md', 'title: ' . $jaar);
    }
    
    if (!is_dir($exportdir . $jaar .'/'. $maand)) {
        mkdir($exportdir . $jaar .'/'. $maand);
        file_put_contents($exportdir . $jaar .'/'. $maand. '/blog_month.md', 'title: ' . $maanddisplay);
    }

    // afh van status de folder herzien
    if ($article['status'] == 'draft') {
        $postfolder = '_drafts/' . $cleantitle;

        if (!is_dir($exportdir . $jaar .'/'. $maand . '/_drafts')) {
            mkdir($exportdir . $jaar .'/'. $maand . '/_drafts' );
        }

        if (!is_dir($exportdir . $jaar .'/'. $maand . '/_drafts/' . $cleantitle)) {
            mkdir($exportdir . $jaar .'/'. $maand . '/_drafts/' . $cleantitle);
        }
    }
    else {
        $postfolder = $tmpdate . '_' . $cleantitle;

        if (!is_dir($exportdir . $jaar .'/'. $maand .'/'. $postfolder)) {
            mkdir($exportdir . $jaar .'/'. $maand .'/'. $postfolder);
        }
    }
    
    // download imgs and place in relevant folder
    if ($article['thumbnail'] !== '') {

        $strArray = explode('/',$article['thumbnail']);
        $thumbnail_filename = end($strArray);

        $ch = curl_init($article['thumbnail']);
        $fp = fopen($exportdir . $jaar .'/'. $maand .'/'. $postfolder . '/' . $thumbnail_filename, "w+");

        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        echo 'File copied: ' . $exportdir . $jaar .'/'. $maand .'/'. $postfolder . '/' . $thumbnail_filename . '<br />';
    }
    else {
        $thumbnail_filename = '';
    }

    //Compile the content of the file to write
    $strtowrite = "Title: " . $article['title']
        . PHP_EOL . "----" . PHP_EOL
        . "Date: " . date('Y-m-d H:i', $article['timestamp'])
        . PHP_EOL . "----" . PHP_EOL
        . "Category: " . implode(', ', $categories)
        . PHP_EOL . "----" . PHP_EOL
        . "Tags: " . implode(', ', $tags)
        . PHP_EOL . "----" . PHP_EOL
        . "Header: " . $thumbnail_filename
        . PHP_EOL . "----" . PHP_EOL
        . "Intro: " . $content_summary
        . PHP_EOL . "----" . PHP_EOL
        . "Text: " . $content_moretext;

    // TODO posts with multiple cats?
    
    //Save to file
    file_put_contents($exportdir . $jaar .'/'. $maand .'/'. $postfolder. '/blogpost.md', $strtowrite);
    echo 'File written: ' . $exportdir . $jaar .'/'. $maand .'/'. $postfolder . ' at ' . date('Y-m-d H:i:s') . '<br />';
}
?>

<!-- http://stayregular.net/blog/from-wordpress-to-kirby-part-2-migrating-data -->