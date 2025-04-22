<?php

class A0CumhurriyetYazarlarBridge extends BridgeAbstract {
    const NAME = 'Cumhuriyet Yazarlar';
    const URI = 'https://www.cumhuriyet.com.tr/rss/1';
    const DESCRIPTION = 'Generates RSS feeds for Cumhuriyet writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $xml = simplexml_load_file(self::URI)
            or returnServerError('Could not request ' . self::URI);

        foreach ($xml->channel->item as $xmlItem) {
            $item = [];

            // Get title and link from XML
            $item['title'] = (string)$xmlItem->title;
            $item['uri'] = (string)$xmlItem->link;

            // Get thumbnail from XML
            if (isset($xmlItem->enclosure['url'])) {
                $item['enclosures'][] = (string)$xmlItem->enclosure['url'];
                $item['thumbnail'] = (string)$xmlItem->enclosure['url'];
            }

            // Get publication date from XML
            if (isset($xmlItem->pubDate)) {
                $item['timestamp'] = strtotime((string)$xmlItem->pubDate);
            }

            // Start building content with thumbnail
            $contentHtml = '';
            if (isset($item['thumbnail'])) {
                $contentHtml = '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
            }

            // Fetch the article page for content and author
            $articlePage = getSimpleHTMLDOM($item['uri']);
            if (!$articlePage) {
                // Skip this article if the content could not be fetched
                continue;
            }

            // Get author
            $authorElement = $articlePage->find('div.adi', 0);
            if ($authorElement) {
                $item['author'] = trim($authorElement->plaintext);
            }

            // Get content
            $contentElement = $articlePage->find('div.haberMetni', 0);
            if ($contentElement) {
                // Remove unwanted elements
                foreach ($contentElement->find('p[class*="inad-text"]') as $unwanted) {
                    $unwanted->outertext = '';
                }
                
                // Append article content to thumbnail
                $contentHtml .= $contentElement->innertext;
            }

            $item['content'] = $contentHtml;
            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Cumhuriyet Yazarlar';
    }
}
