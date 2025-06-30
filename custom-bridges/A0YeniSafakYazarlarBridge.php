<?php
// filepath: /home/yonetici/docker/rss-bridge/custom-bridges/A0YeniSafakYazarlarBridge.php

class A0YeniSafakYazarlarBridge extends BridgeAbstract {
    const NAME = 'Yeni Şafak Yazarlar';
    const URI = 'https://www.yenisafak.com/rss-feeds?authorType=newspaper&contentType=column';
    const DESCRIPTION = 'Yeni Şafak köşe yazarları için özel RSS köprüsü';
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

            // Get author from <subcategory>
            $author = isset($xmlItem->subcategory) ? trim((string)$xmlItem->subcategory) : '';

            // Get title and format as "Author : Title"
            $title = (string)$xmlItem->title;
            if ($author) {
                $title = $author . ' : ' . $title;
            }
            $item['title'] = $title;

            // Get link
            $item['uri'] = (string)$xmlItem->link;

            // Get publication date
            if (isset($xmlItem->pubDate)) {
                $item['timestamp'] = strtotime((string)$xmlItem->pubDate);
            }

            // Get content from <content:encoded>
            $namespaces = $xmlItem->getNamespaces(true);
            $content = '';
            if (isset($namespaces['content'])) {
                $contentEncoded = $xmlItem->children($namespaces['content']);
                if (isset($contentEncoded->encoded) && !empty($contentEncoded->encoded)) {
                    $content = (string)$contentEncoded->encoded;

                    // Extract SoundCloud link from the beginning of the content
                    if (function_exists('str_get_html')) {
                        $html = str_get_html($content);
                        if ($html) {
                            $span = $html->find('span.pho-card-embed', 0);
                            if ($span && isset($span->attr['data-url'])) {
                                $soundcloudUrl = $span->attr['data-url'];
                                // Remove the embed span from the content
                                $span->outertext = '';
                                // Add the SoundCloud link at the top
                                $content = '<a href="' . htmlspecialchars($soundcloudUrl) . '" target="_blank">🔊 SoundCloud ile dinle</a><br><br>' . $html;
                                $item['enclosures'][] = $soundcloudUrl;
                            }
                        }
                    }
                }
            }
            $item['content'] = $content;
            $item['author'] = $author;
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }

    public function getName() {
        return self::NAME;
    }
}