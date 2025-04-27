<?php

class A0SozcuYazarlarBridge extends BridgeAbstract {
    const NAME = 'Sozcu Yazarlar Bridge';
    const URI = 'https://www.sozcu.com.tr/feeds-rss-category-yazar';
    const DESCRIPTION = 'Generates RSS feeds for Sozcu writers';
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

        // Register the media namespace
        $xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/');

        foreach ($xml->channel->item as $xmlItem) {
            $item = [];

            // Get link and guid from XML
            $item['uri'] = (string)$xmlItem->link;

            // Get publication date from XML
            if (isset($xmlItem->pubDate)) {
                $item['timestamp'] = strtotime((string)$xmlItem->pubDate);
            }

            // Get thumbnail from media:content
            $mediaContent = $xmlItem->children('http://search.yahoo.com/mrss/');
            if (isset($mediaContent->content)) {
                $item['thumbnail'] = (string)$mediaContent->content->attributes()->url;
                $item['enclosures'][] = $item['thumbnail'];
            }

            // Fetch the article page for content, author, and title
            try {
                $articlePage = getSimpleHTMLDOM($item['uri']);
            if (!$articlePage) {
                // Skip this article if the content could not be fetched
                continue;
            }

            // Check for SSL or cURL errors
            if (isset($articlePage->innertext) && 
                (strpos($articlePage->innertext, 'error') !== false || 
                 strpos($articlePage->innertext, 'SSL') !== false || 
                 strpos($articlePage->innertext, 'cURL') !== false)) {
                continue; // Skip if the page contains error information
            }

            // Get author
            $authorElement = $articlePage->find('div.content-meta-name', 0);
            if ($authorElement) {
                $item['author'] = html_entity_decode(trim($authorElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Get title
            $titleElement = $articlePage->find('h1.author-content-title', 0);
            if ($titleElement) {
                $articleTitle = html_entity_decode(trim($titleElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Combine author and title
                $item['title'] = $item['author'] . ' : ' . $articleTitle;
            }

            // Get content
            $contentElement = $articlePage->find('div.article-body', 0);
            if ($contentElement) {
                // Start content with thumbnail
                $contentHtml = '';
                if (isset($item['thumbnail'])) {
                    $contentHtml = '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
                }
                
                // Add article content
                $contentHtml .= $contentElement->innertext;
                $item['content'] = $contentHtml;
            }
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
        return 'Sözcü Yazarlar';
    }
}