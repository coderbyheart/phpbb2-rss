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
    * Topic feed
    *
    * @link http://projects.tacker.org/trac/phpbb-rss
    * @author Markus Tacker <m@tacker.org>
    * @version $Id: t.php 342 2007-05-15 15:05:25Z m $
    */

    define ('IN_PHPBB', true);
    require_once './common.php';

    if (!RSS_ENABLE_TOPIC_FEED) {
        error('Topic feed disabled.');
        return;
    }

    $topic_id = false;
    if (isset($_GET['tid'])) {
        $topic_id = intval($_GET['tid']);
        if ($topic_id == 0) {
            error('You must provide a valid topic id.');
            exit;
        }
    } else {
        error('You must provide a topic id.');
        exit;
    }

    // Get the time of the last post
    $sql = 'SELECT MAX(p.post_time) AS max_post_time FROM ' . POSTS_TABLE . ' p';
    if ($forum_id) {
        $sql .= ' WHERE p.topic_id = ' . $topic_id;
    }
    if (!$result = $db->sql_query($sql)) {
        error('A database error occurred. (1)');
        exit;
    }
    $row = $db->sql_fetchrow($result);
    $max_post_time = $row['max_post_time'];

    // Build etag
    $etag = 'RSS.TOPIC.' . $topic_id . '.' . $max_post_time;

    // Cache control
    exitIfNotModified($max_post_time);
    exitIfEtagsMatch($etag);

    // Get the topic
    $sql = 'SELECT t.topic_title, f.forum_name FROM ' . TOPICS_TABLE . ' t LEFT JOIN ' . FORUMS_TABLE . ' f ON f.forum_id = t.forum_id AND f.auth_view = ' . AUTH_ALL . ' WHERE t.topic_id = ' . $topic_id;
    // Query
    if (!$result = $db->sql_query($sql)) {
        error('A database error occurred. (2)');
        exit;
    }
    $row = $db->sql_fetchrow($result);
    if (empty($row)) {
        error('The topic does not exist.');
        exit;
    }
    if (empty($row['forum_name'])) {
        error('The forum does not exist.');
        exit;
    }

    // Build query
    // Get posts
    $sql = 'SELECT p.post_id, p.post_time, b.post_text, b.post_subject, "' . $row['forum_name'] . '" AS forum_name, "' . $row['topic_title'] . '" AS topic_title FROM ' . POSTS_TABLE . ' p ';
    if (RSS_ENABLE_USER) {
        $sql = str_replace(' FROM', ', p.poster_id, u.username FROM', $sql);
        $sql .= ' LEFT JOIN ' . USERS_TABLE . ' u ON p.poster_id = u.user_id ';
    }
    // Body
    $sql .= ' LEFT JOIN ' . POSTS_TEXT_TABLE . ' b ON p.post_id = b.post_id ';
    $sql .= ' WHERE p.topic_id = ' . $topic_id;
    $sql .= ' ORDER BY p.post_time DESC ';
    $sql .= ' LIMIT ' . RSS_RESULT_LIMIT;

    // Query
    if (!$result = $db->sql_query($sql)) {
        error('A database error occurred. (3)');
        exit;
    }
    // Build item XML
    $items = '';
    $data = $db->sql_fetchrowset($result);
    if (is_array($data)) {
        foreach ($data as $item) {

            $items .= "            <item>\n";
            $items .= '                <title>'. e(((empty($item['post_subject'])) ? $item['topic_title'] : $item['post_subject'])) . "</title>\n";
            $items .= '                <link>'. $forum_url . 'viewtopic.' . $phpEx . '?t=' . $topic_id . "</link>\n";
            $items .= '                <guid>'. $forum_url . 'viewtopic.' . $phpEx . '?t=' . $topic_id . "</guid>\n";
            $items .= '                <comments>'. $forum_url . 'posting.' . $phpEx . '?mode=reply&amp;t=' . $topic_id . "</comments>\n";
            $items .= '                <pubDate>'. date('D, j M Y H:i:s O', $item['post_time']) . "</pubDate>\n";
            if (RSS_ENABLE_USER) {
                $items .= '                <dc:creator>'. e($item['username']) . "</dc:creator>\n"; // Author
            }
            if (RSS_ENABLE_FORUM) {
                $items .= '                <category>'. htmlspecialchars(e($item['forum_name'])) . "</category>\n"; // Forum
            }
            if (RSS_ENABLE_BODY) {
                if ($BBCodeParser) {
                    $BBCodeParser->setText($item['post_text']);
                    $BBCodeParser->parse();
                    $body = $BBCodeParser->getParsed();
                } else {
                    $body = $item['post_text'];
                }
                $items .= '                <description>'. htmlspecialchars(nl2br(e(c($body)))) . "</description>\n"; // Body,  clean text
            } else {
                $items .= "                <description>No preview available.</description>\n"; // Body,  clean text
            }
            $items .= "            </item>\n";
        }
    }

    // Close DB
    $db->sql_close();

    // Send XML
    send($items, ' :: ' . $item['forum_name'] . ' :: ' . $item['topic_title']);

?>