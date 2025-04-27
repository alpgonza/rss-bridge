<?php
class A0HalkTVYazarlarBridge extends BridgeAbstract {
    const NAME = 'Halk TV Yazarlar';
    const URI = 'https://halktv.com.tr/yazar';
    const DESCRIPTION = 'Fetches articles from Halk TV Yazarlar';
    const MAINTAINER = 'Alpgonza';
    const PARAMETERS = [
        [
            'fetch_full_content' => [
                'name' => 'Fetch Full Content',
                'type' => 'checkbox',
                'title' => 'Enable to fetch full article content',
            ]
        ]
    ];
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        // Fetch the main page
        try {
            $html = getSimpleHTMLDOM(self::URI);
            if (!$html) {
                // Silently return if the main page couldn't be fetched
                return;
            }
        } catch (Exception $e) {
            // Log the error for debugging (optional)
            // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching main page: ' . $e->getMessage() . "\n", FILE_APPEND);
            return; // Silently return to avoid error feed
        }

        $fetchFullContent = $this->getInput('fetch_full_content');

        foreach ($html->find('section.item') as $element) {
            $item = [];

            // Extract author name
            $authorElement = $element->find('a[rel="author"]', 0);
            $author = $authorElement ? $authorElement->plaintext : 'Unknown';

            // Extract title and URL from <h3>
            $titleElement = $element->find('h3 a', 0);
            if (!$titleElement) {
                continue; // Skip if title element is missing
            }
            $title = $titleElement->plaintext;
            $relativeUri = $titleElement->href;
            $uri = 'https://halktv.com.tr' . $relativeUri; // Ensure the URL is absolute

            // Extract thumbnail
            $thumbnailElement = $element->find('img', 0);
            $thumbnail = $thumbnailElement ? $thumbnailElement->src : '';

            // If "Fetch Full Content" is enabled, fetch the article content
            $contentHtml = '';
            if ($fetchFullContent) {
                try {
                    $articleHtml = getSimpleHTMLDOM($uri);
                if (!$articleHtml) {
                    // Skip this article if the content could not be fetched
                    continue;
                }

                    // Check for error pages (e.g., SSL or server errors)
                if (isset($articleHtml->innertext) && 
                    (strpos($articleHtml->innertext, 'error') !== false || 
                     strpos($articleHtml->innertext, 'SSL') !== false || 
                     strpos($articleHtml->innertext, 'cURL') !== false)) {
                    continue; // Skip if the page contains error information
                }

                // Extract article content
                $contentElement = $articleHtml->find('div.text-content', 0);
                if ($contentElement) {
                    // Remove unwanted elements (e.g., banners)
                    foreach ($contentElement->find('div[class*="banner"]') as $unwanted) {
                        $unwanted->outertext = '';
                    }

                    // Start with thumbnail if available
                    if ($thumbnail) {
                        $contentHtml = '<img src="' . $thumbnail . '" /><br/><br/>';
                    }

                    // Add article content
                    $contentHtml .= $contentElement->innertext;
                }

                // Get article date
                $dateElement = $articleHtml->find('div.content-date time', 0);
                if ($dateElement) {
                    $item['timestamp'] = strtotime($dateElement->datetime);
                }
                } catch (Exception $e) {
                    // Skip this article if an HTTP error (e.g., 500) occurs
                    // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $uri . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                    continue;
                }
            }

            // Populate item data
            $item['title'] = $author . ' : ' . $title;
            $item['uri'] = $uri;
            $item['author'] = $author;
            $item['enclosures'] = $thumbnail ? [$thumbnail] : [];
            $item['uid'] = $uri;
            $item['content'] = $fetchFullContent ? $contentHtml : '';

            $this->items[] = $item;
        }
    }
}