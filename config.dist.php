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
    * Optional Configuration
    *
    * This file may contain configuration constant which you wish to
    * overwrite.
    * Change to suite your needs and rename this file to config.php
    *
    * @link http://projects.tacker.org/trac/phpbb-rss
    * @author Markus Tacker <m@tacker.org>
    * @version $Id: config.dist.php 341 2007-05-15 10:56:54Z m $
    */

    /**
    * @var string content language of your feed
    */
    define('RSS_CHANNEL_LANGUAGE', 'de-de');

    /**
    * @var bool Enable the name of the forum as "category" value?
    */
    define('RSS_ENABLE_FORUM', true);

    /**
    * @var bool Enable the name of the user as "dc:creator" value?
    */
    define('RSS_ENABLE_USER', true);

    /**
    * @var bool Display the post's body in the feed
    */
    define('RSS_ENABLE_BODY', true);

    /**
    * @var bool Enable the feed for single topics
    */
    define('RSS_ENABLE_TOPIC_FEED', true);

    /**
    * @var int limit the return posts by this value
    */
    define('RSS_RESULT_LIMIT', 10);

    /**
    * @var int Cache time in seconds
    */
    define('RSS_CACHE_TIME', 300);

?>