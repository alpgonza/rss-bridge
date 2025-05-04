<?php

class A0SabahYazarlarBridge extends FeedExpander {
    const NAME = 'Sabah Yazarlar';
    const URI = 'https://www.sabah.com.tr';
    const DESCRIPTION = 'Fetches articles from Sabah Yazarlar RSS feed';
    const MAINTAINER = 'Alpgonza';
    const PARAMETERS = [];
    const FEED_URL = 'https://www.sabah.com.tr/rss/yazarlar.xml';

    public function collectData() {
        // Fetch the RSS feed
        $rssContent = @file_get_contents(self::FEED_URL);
        if ($rssContent === false) {
            returnServerError('Could not fetch the RSS feed.');
        }

        // Parse the XML feed
        libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($rssContent);
        libxml_clear_errors();
        if ($feed === false) {
            returnServerError('Could not parse the RSS feed.');
        }

        foreach ($feed->channel->item as $rssItem) {
            $item = [];

            // Get the article link and title directly from the RSS feed
            $item['uri'] = (string)$rssItem->link;
            $item['title'] = (string)$rssItem->title;

            // Fetch the article page
            $articleHtml = @getSimpleHTMLDOM($item['uri']);
            if (!$articleHtml) {
                // Skip this article if the content could not be fetched
                continue;
            }

            // Extract the article content
            $contentElement = $articleHtml->find('div.newsBox', 0);
            if ($contentElement) {
                $item['content'] = $contentElement->innertext;
            } else {
                $item['content'] = 'Content could not be fetched.';
            }

            // Add the publication date
            $item['timestamp'] = strtotime((string)$rssItem->pubDate);

            // Add the item to the feed
            $this->items[] = $item;
        }
    }
}