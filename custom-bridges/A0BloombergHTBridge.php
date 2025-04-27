<?php

class A0BloombergHTBridge extends BridgeAbstract {
    const NAME = 'Bloomberg HT News';
    const URI = 'https://www.bloomberght.com/';
    const DESCRIPTION = 'Generates RSS feeds with full content from Bloomberg HT';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const PARAMETERS = []; // No parameters required

    public function collectData() {
        $rssFeedUrl = self::URI . 'rss';

        // Fetch the RSS feed
        try {
            $rssContent = getSimpleHTMLDOM($rssFeedUrl);
            if (!$rssContent) {
                // Silently return if the RSS feed couldn't be fetched
                return;
            }
        } catch (Exception $e) {
            // Log the error for debugging (optional)
            // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching RSS: ' . $e->getMessage() . "\n", FILE_APPEND);
            return; // Silently return to avoid error feed
        }

        // Parse each <item> element from the RSS feed
        foreach ($rssContent->find('item') as $rssItem) {
            $item = [];

            // Clean title from CDATA
            $title = $rssItem->find('title', 0)->innertext;
            $item['title'] = trim(str_replace(['<![CDATA[', ']]>'], '', $title));
            
            // Clean URL from CDATA sections and whitespace
            $guid = $rssItem->find('guid', 0)->innertext;
            $item['uri'] = trim(str_replace(['<![CDATA[', ']]>'], '', $guid));
            
            $item['timestamp'] = strtotime($rssItem->find('pubDate', 0)->plaintext);
            
            // Add RSS description as fallback
            $item['content'] = $rssItem->find('description', 0)->plaintext;

            // Add image from RSS feed if available
            $imageElement = $rssItem->find('image', 0);
            if ($imageElement) {
                $imageUrl = trim($imageElement->innertext);
                $item['enclosures'][] = $imageUrl;
                $item['content'] = '<img src="' . $imageUrl . '" alt="News Image"><br>' . $item['content'];
            }

            // Fetch the full article content from the linked page
            try {
            $linkedPage = getSimpleHTMLDOM($item['uri']);
            if (!$linkedPage) {
                // Skip this article if the content could not be fetched
                continue;
            }

                // Check for error pages (e.g., SSL or server errors)
                if (isset($linkedPage->innertext) &&
                    (strpos($linkedPage->innertext, 'error') !== false ||
                     strpos($linkedPage->innertext, 'SSL') !== false ||
                     strpos($linkedPage->innertext, 'cURL') !== false)) {
                    continue; // Skip if the page contains error information
                }

            // Get article description from h2 elements and maintain bullet style
            $descriptions = [];
            foreach ($linkedPage->find('div.py-4 ul li h2.description') as $desc) {
                $descriptions[] = '<li class="relative" style="padding-left: 20px; margin-bottom: 1em; position: relative;">
                    <span style="position: absolute; left: 0; top: 0.5em; width: 8px; height: 8px; background-color: black; display: inline-block;"></span>
                    <strong>' . trim($desc->plaintext) . '</strong>
                </li>';
            }
            
            // Combine descriptions into a styled unordered list
            $articleDescription = !empty($descriptions) ? 
                '<ul style="list-style: none; margin: 1em 0;">' . implode('', $descriptions) . '</ul>' : '';

            // Get article content paragraphs
            $contentElements = $linkedPage->find('div.article-wrapper p');
                $contentHtml = '';
                if ($contentElements) {
                foreach ($contentElements as $element) {
                    $contentHtml .= '<p>' . $element->plaintext . '</p>';
                }
            }

            // Fix: Update image selector to match actual HTML structure
            $imageElement = $linkedPage->find('div.aspect-video img', 0);
            $imageHtml = $imageElement ? '<img src="' . $imageElement->getAttribute('src') . '" alt="News Image"><br>' : '';

            // Combine all content elements
            $fullContent = $articleDescription . $imageHtml . $contentHtml;
            if (!empty($fullContent)) {
                $item['content'] = $fullContent;
            }

            // Add the image as an enclosure
            if ($imageElement) {
                    $item['enclosures'][] = $imageElement->getAttribute('data-src') ?: $imageElement->getAttribute('src');
                }
            } catch (Exception $e) {
                // Skip this article if an HTTP error (e.g., 500) occurs
                // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $item['uri'] . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }

            $this->items[] = $item;
        }
    }
}