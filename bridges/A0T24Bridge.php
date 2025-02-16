<?php

class A0T24Bridge extends BridgeAbstract {
    const NAME = 'T24 Haberler';
    const URI = 'https://t24.com.tr/';
    const DESCRIPTION = 'Generates RSS feeds with full content for T24 articles';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $rssFeedUrl = self::URI . 'rss';
        $rssContent = getSimpleHTMLDOM($rssFeedUrl)
            or returnServerError('Could not fetch T24 RSS feed');

        foreach ($rssContent->find('item') as $itemElement) {
            $item = [];

            // Clean title from CDATA
            $title = $itemElement->find('title', 0)->innertext;
            $item['title'] = trim(str_replace(['<![CDATA[', ']]>'], '', $title));

            // Clean URL from CDATA sections and whitespace
            $guid = $itemElement->find('guid', 0)->innertext;
            $item['uri'] = trim(str_replace(['<![CDATA[', ']]>'], '', $guid));

            // Extract publication date from RSS feed
            $pubDate = $itemElement->find('pubDate', 0)->plaintext;
            $item['timestamp'] = strtotime($pubDate);

            // Get description as fallback content
            $description = $itemElement->find('description', 0)->innertext;
            $item['content'] = trim(str_replace(['<![CDATA[', ']]>'], '', $description));

            // Get image from RSS enclosure
            $enclosure = $itemElement->find('enclosure', 0);
            $imageHtml = '';
            if ($enclosure && $enclosure->url) {
                $imageUrl = $enclosure->url;
                $item['enclosures'][] = $imageUrl;
                $imageHtml = '<img src="' . $imageUrl . '" /><br>';
            }

            // Fetch full article content
            $articlePage = getSimpleHTMLDOM($item['uri'])
                or returnServerError('Could not fetch article page: ' . $item['uri']);

            if ($articlePage) {
                // Get article content from the correct div
                $contentDiv = $articlePage->find('div._3QVZl', 0);
                if ($contentDiv) {
                    // Remove table elements
                    foreach ($contentDiv->find('table') as $table) {
                        $table->outertext = '';
                    }

                    // Remove script elements
                    foreach ($contentDiv->find('script') as $script) {
                        $script->outertext = '';
                    }

                    // Get the entire content of the div
                    $contentHtml = $contentDiv->innertext;

                    // Combine image and content
                    if (!empty($contentHtml)) {
                        $item['content'] = $imageHtml . $contentHtml;
                    }
                }
            }

            $this->items[] = $item;
        }
    }
}

