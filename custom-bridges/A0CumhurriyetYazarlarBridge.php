<?php

class A0CumhurriyetYazarlarBridge extends BridgeAbstract {
    const NAME = 'Cumhuriyet Yazarlar';
    const URI = 'https://www.cumhuriyet.com.tr/rss/1';
    const DESCRIPTION = 'Generates RSS feeds for Cumhuriyet writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        // Fetch the XML feed
        try {
            $xml = simplexml_load_file(self::URI);
            if (!$xml) {
                // Silently return if the XML feed couldn't be fetched
                return;
            }
        } catch (Exception $e) {
            // Log the error for debugging (optional)
            // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching XML: ' . $e->getMessage() . "\n", FILE_APPEND);
            return; // Silently return to avoid error feed
        }

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
            try {
                $articlePage = getSimpleHTMLDOM($item['uri']);
            if (!$articlePage) {
                // Skip this article if the content could not be fetched
                continue;
            }

                // Check for error pages (e.g., SSL or server errors)
            if (isset($articlePage->innertext) && 
                (strpos($articlePage->innertext, 'error') !== false || 
                 strpos($articlePage->innertext, 'SSL') !== false || 
                 strpos($articlePage->innertext, 'cURL') !== false)) {
                continue; // Skip if the page contains error information
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
            } catch (Exception $e) {
                // Skip this article if an HTTP error (e.g., 500) occurs
                // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $item['uri'] . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }

            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Cumhuriyet Yazarlar';
    }
}