<?php

    // +----------------------------------------------------------------------+
    // | RSS for PHPBB                                                        |
    // +----------------------------------------------------------------------+
    // | Copyright (C) Markus Tacker <m@tacker.org>                           |
    // +----------------------------------------------------------------------+
    // | This library is free software; you can redistribute it and/or        |
    // | modify it under the terms of the GNU Lesser General Public           |
    // | License as published by the Free Software Foundation; either         |
    // | version 2.1 of the License, or (at your option) any later version.   |
    // |                                                                      |
    // | This library is distributed in the hope that it will be useful,      |
    // | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
    // | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
    // | Lesser General Public License for more details.                      |
    // |                                                                      |
    // | You should have received a copy of the GNU Lesser General Public     |
    // | License along with this library; if not, write to the                |
    // | Free Software Foundation, Inc.                                       |
    // | 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA               |
    // +----------------------------------------------------------------------+

    /**
    * RSS for PHPBB
    *
    * Configuration and common functions
    *
    * @link http://projects.tacker.org/trac/phpbb-rss
    * @author Markus Tacker <m@tacker.org>
    * @version $Id: common.php 343 2007-05-15 15:10:17Z m $
    */

    // Timer start
    $t_start = explode(' ', microtime());
    $t_start = $t_start[1] + $t_start[0];

    // Include optional user defined configuration options
    @include_once './config.php';
    ini_set('display_errors', 1);

    /**
    * @var string content language of your feed
    */
    if (!defined('RSS_CHANNEL_LANGUAGE')) {
        define('RSS_CHANNEL_LANGUAGE', 'de-de');
    }

    /**
    * @var bool Enable the name of the forum as "category" value?
    */
    if (!defined('RSS_ENABLE_FORUM')) {
        define('RSS_ENABLE_FORUM', true);
    }

    /**
    * @var bool Enable the name of the user as "dc:creator" value?
    */
    if (!defined('RSS_ENABLE_USER')) {
        define('RSS_ENABLE_USER', true);
    }

    /**
    * @var bool Display the post's body in the feed
    */
    if (!defined('RSS_ENABLE_BODY')) {
        define('RSS_ENABLE_BODY', true);
    }

    /**
    * @var bool Enable the feed for single topics
    */
    if (!defined('RSS_ENABLE_TOPIC_FEED')) {
        define('RSS_ENABLE_TOPIC_FEED', true);
    }

    /**
    * @var int limit the return posts by this value
    */
    if (!defined('RSS_RESULT_LIMIT')) {
        define('RSS_RESULT_LIMIT', 10);
    }

    /**
    * @var int Cache time in seconds
    */
    if (!defined('RSS_CACHE_TIME')) {
        define('RSS_CACHE_TIME', 300);
    }

    // Include PHPBB stuff
    $phpbb_root_path = '../';
    require_once $phpbb_root_path . 'extension.inc';
    require_once $phpbb_root_path . 'common.' . $phpEx;

    // Bail out if board is disabled
    if ($board_config['board_disable']) {
        return;
    }

    // Build forum URL
    $forum_url = (isset($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://';
    $forum_url .= $board_config['server_name'];
    if ($board_config['server_port'] != '80') {
        $forum_url .= ':' . $board_config['server_port'];
    }
    $forum_url .= $board_config['script_path'];

    // Build url for comments = posts in a single topic
    $comment_feed_url = (isset($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/t.php';

    // Build url for forums
    $forum_feed_url = (isset($_SERVER['HTTPS']) and ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/f.php';

    // Latest post time
    $max_post_time = 0;

    // Etag to use
    $etag = false;

    /**
    * Send items XML or error XML
    *
    * @param string items xml
    */
    function send($items, $title = 'feed', $forum_id = null)
    {
        global $board_config, $forum_url, $phpEx, $t_start, $max_post_time, $etag;

        // Titles should not contain html
        $title = preg_replace('/&[^ ;]+;/', '', $title); // Removes entities

        // Timer end
        $t_end = explode(' ', microtime());
        $t_end = $t_end[1] + $t_end[0];

        // Output
        header('Content-type: text/xml; charset=UTF-8');
        header('Expires: ' . date('D, j M Y H:i:s O', time() + RSS_CACHE_TIME));
        if ($max_post_time > 0) {
            // Send Last-Modified header with date of latest post
            header('Last-Modified: ' . date('D, j M Y H:i:s O', $max_post_time), false);
        }
        if ($etag) {
            header('Etag: ' . $etag, false);
        }
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . "<!-- \n"
        . "    RSS generated by RSS for PHPBB, http://projects.tacker.org/trac/phpbb-rss\n"
        . '    Page generation took ' . round(($t_end - $t_start), 4) . " seconds.\n"
        . "    Have a nice day.\n"
        . "-->\n"
        . "<rss version=\"2.0\"\n"
        . "    xmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n"
        . "    xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n"
        . ">\n"
        . "    <channel>\n"
        . '        <title>' . htmlspecialchars(e($board_config['sitename'])) . ' ' . htmlspecialchars(e($title)) . "</title>\n"
        . '        <link>' . $forum_url . (($forum_id) ? 'viewforum.' . $phpEx . '?f=' . $forum_id : '') . "</link>\n"
        . '        <description>' . htmlspecialchars(e($board_config['site_desc'])) . "</description>\n"
        . '        <pubDate>' . date('D, j M Y H:i:s O') . "</pubDate>\n"
        . "        <generator>RSS for PHPBB, http://projects.tacker.org/trac/phpbb-rss</generator>\n"
        . '        <language>' . RSS_CHANNEL_LANGUAGE . "</language>\n"
        . $items
        . "    </channel>\n"
        . "</rss>\n";
    }

    /**
    * Send an error message as XML
    *
    * @param string error message
    */
    function error($msg)
    {
        header('Content-type: text/xml; charset=UTF-8');
        header($msg, true, 500);
        echo '<error>' . e($msg) . "</error>\n";
    }

    /**
    * Output safe encode a string
    *
    * @param string
    * @return string
    */
    function e($str)
    {
        return utf8_encode($str);
    }

    /**
    * Returns a cleaned version of the string
    *
    * @param string
    * @param bool
    * @return string
    */
    function c($str, $ifbody = true)
    {
        global $board_config;
        if ($board_config['allow_html']) {
            $str = strip_tags($str); // HTML, in case it is allowed
        }
        if ($board_config['allow_bbcode']) {
            // Images
            $str = preg_replace('%\[img[^\]]*\]([^\[]+)\[/img[^\]]*\]%', '<img src="\1" />', $str);
            // URLs
            $str = preg_replace('%\[url\]([^\[]+)\[/url\]%', '<a href="\1">\1</a>', $str);
            $str = preg_replace('%\[url=([^\]]+)\]([^\[]+)\[/url\]%', '<a href="\1">\2</a>', $str);
            // List-Items
            $str = preg_replace('%\[\*[^\]]+\]([^\[\s]+)\s*%', '<li>\1</li>', $str);
            // $str = preg_replace('%%', '', $str);
            // Simple
            $bbreplace = array(
                'b' => 'strong',
                'i' => 'em',
                'u' => 'em',
                'quote' => 'blockquote',
                'code' => 'code',
                'list' => 'ul',
            );
            foreach ($bbreplace as $s => $r) {
                $str = preg_replace('%\[' . $s . '[^\]]*\]([^\[]+)\[/' . $s . '[^\]]*\]%', '<' . $r . '>\1</' . $r . '>', $str);
            }
            // remove unrecognized BBCode
            $str = preg_replace('/\[\S[^\]]+\]/', '', $str);
        }
        if ($board_config['allow_smilies']) {
            $str = preg_replace('/:[a-z]+:/', '', $str);  // Smilies
        }
        return trim($str);
    }

    /**
    * Exit if the client has send a modified since header and no newer data is found
    */
    function exitIfNotModified($last_modification_time)
    {
        // Check if the client has sent a modified since header
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            // We do not send data if there are no new posts
            if ($modified_since > $last_modification_time) {
                header('Not Modified', false, 304);
                exit;
            }
        }
    }

    /**
    * Exit if the etags match
    */
    function exitIfEtagsMatch($etag)
    {
        // Check if the client sends an etag
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])
        and $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            header('Not Modified', false, 304);
            exit;
        }
    }

?>