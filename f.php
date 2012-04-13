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
    * Forum feed
    *
    * @link http://projects.tacker.org/trac/phpbb-rss
    * @author Markus Tacker <m@tacker.org>
    * @version $Id: f.php 342 2007-05-15 15:05:25Z m $
    */

    define ('IN_PHPBB', true);
    require_once './common.php';

    // Check request parameters
    $forum_id = false;
    if (isset($_GET['fid'])) {
        $forum_id = intval($_GET['fid']);
        if ($forum_id == 0) {
            error('You must provide a valid forum id.');
            exit;
        }
    }

    // Get the time of the last post
    $sql = 'SELECT MAX(p.post_time) AS max_post_time FROM ' . POSTS_TABLE . ' p';
    if ($forum_id) {
        $sql .= ' WHERE p.forum_id = ' . $forum_id;
    }
    if (!$result = $db->sql_query($sql)) {
        error('A database error occurred. (1)');
        exit;
    }
    $row = $db->sql_fetchrow($result);
    $max_post_time = $row['max_post_time'];

    // Build etag
    $etag = 'RSS.FORUM.' . (($forum_id) ? $forum_id . '.' : '') . $max_post_time;

    // Cache control
    exitIfNotModified($max_post_time);
    exitIfEtagsMatch($etag);

    // Build query
    $sql = 'SELECT t.topic_id, t.topic_title, t.topic_time FROM ' . TOPICS_TABLE . ' t ';
    // Get the name of the user
    if (RSS_ENABLE_USER) {
        $sql = str_replace(' FROM', ', t.topic_poster, u.username FROM', $sql);
        $sql .= ' LEFT JOIN ' . USERS_TABLE . ' u ON t.topic_poster = u.user_id ';
    }
    // Get the body of the message
    if (RSS_ENABLE_BODY) {
        $sql = str_replace(' FROM', ', t.topic_first_post_id, b.post_text FROM', $sql);
        $sql .= ' LEFT JOIN ' . POSTS_TEXT_TABLE . ' b ON t.topic_first_post_id = b.post_id ';
    }
    if ($forum_id) {
        // Get the forum name
        $sql_forum = 'SELECT f.forum_name FROM ' . FORUMS_TABLE . ' f WHERE f.forum_id = ' . $forum_id . ' AND f.auth_view = ' . AUTH_ALL;
        if (!$result = $db->sql_query($sql_forum)) {
            error('A database error occurred. (2)');
            exit;
        }
        $row = $db->sql_fetchrow($result);
        if (empty($row)) {
            error('This forum does not exist.');
            exit;
        }
        $sql = str_replace(' FROM', ', "' . $row['forum_name'] . '" AS forum_name FROM', $sql);
        // Get the feed for one forum only
        $sql .= ' WHERE t.forum_id = ' . $forum_id;
    } else {
        // Get the name of the forum
        if (RSS_ENABLE_FORUM) {
            $sql = str_replace(' FROM', ', t.forum_id, f.forum_name FROM', $sql);
            $sql .= ' LEFT JOIN ' . FORUMS_TABLE . ' f ON t.forum_id = f.forum_id AND f.auth_view = ' . AUTH_ALL;
        }
    }
    $sql .= ' ORDER BY t.topic_time DESC ';
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
            // Skip empty forums which are forbidden
            if (empty($item['forum_name'])) continue;
            $items .= "            <item>\n";
            $items .= '                <title>'. e($item['topic_title']) . "</title>\n";
            $items .= '                <link>'. $forum_url . 'viewtopic.' . $phpEx . '?t=' . intval($item['topic_id']) . "</link>\n";
            $items .= '                <guid>'. $forum_url . 'viewtopic.' . $phpEx . '?t=' . intval($item['topic_id']) . "</guid>\n";
            $items .= '                <comments>'. $forum_url . 'posting.' . $phpEx . '?mode=reply&amp;t=' . intval($item['topic_id']) . "</comments>\n";
            $items .= '                <pubDate>'. date('D, j M Y H:i:s O', $item['topic_time']) . "</pubDate>\n";
            if (RSS_ENABLE_USER) {
                $items .= '                <dc:creator>'. e($item['username']) . "</dc:creator>\n"; // Author
            }
            if (RSS_ENABLE_FORUM) {
                $items .= '                <category>'. htmlspecialchars(e($item['forum_name'])) . "</category>\n"; // Forum
            }
            if (RSS_ENABLE_BODY) {
                $items .= '                <description>'. htmlspecialchars(nl2br(e(c($item['post_text'])))) . "</description>\n"; // Body,  clean text
            } else {
                $items .= "                <description>No preview available.</description>\n"; // Body,  clean text
            }
            if (RSS_ENABLE_TOPIC_FEED) {
                $items .= '                <wfw:commentRss>'. $comment_feed_url .'?tid=' . $item['topic_id'] . "</wfw:commentRss>\n"; // RSS-Feed for the topic only
            }
            $items .= "            </item>\n";
        }
    }

    // Close DB
    $db->sql_close();

    // Send XML
    send($items, (($forum_id) ? ' :: ' . $item['forum_name'] : ''), $forum_id);

?>