<?php

class A0CumhurriyetYazarlarBridge extends BridgeAbstract {
    const NAME = 'Cumhuriyet Yazarlar';
    const URI = 'https://www.cumhuriyet.com.tr/rss/yazarlar';
    const DESCRIPTION = 'Generates RSS feeds for Cumhuriyet writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        // Fetch the XML feed
        try {
            $context = stream_context_create([
                'http' => [
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);
            $rssContent = @file_get_contents(self::URI, false, $context);
            if ($rssContent === false) {
                returnServerError('Could not fetch the RSS feed.');
            }

            libxml_use_internal_errors(true);
            $xml = @simplexml_load_string($rssContent);
            libxml_clear_errors();
            if ($xml === false) {
                returnServerError('Could not parse the RSS feed.');
            }
        } catch (Exception $e) {
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
                $articleContext = stream_context_create([
                    'http' => [
                        'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ]
                ]);
                $articlePage = @file_get_contents($item['uri'], false, $articleContext);
                if ($articlePage === false) {
                    continue; // Skip this article if the content could not be fetched
                }

                $articleDom = str_get_html($articlePage);

                // Get author from the <meta> tag with name="articleAuthor"
                $authorElement = $articleDom->find('meta[name="articleAuthor"]', 0);
                if ($authorElement) {
                    $author = html_entity_decode(trim($authorElement->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $item['author'] = $author;

                    // Prepend author name to the title
                    $item['title'] = $author . ' : ' . $item['title'];
                }

                // Get content from the div with class "text-content"
                $contentElement = $articleDom->find('div.text-content', 0);
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
                continue; // Skip this article if an error occurs
            }

            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Cumhuriyet Yazarlar';
    }
}