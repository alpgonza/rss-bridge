<?php
class A0FatihAltayliYazilariBridge extends BridgeAbstract {
    const NAME = 'Fatih Altaylı Yazıları';
    const URI = 'https://fatihaltayli.com.tr/yazilar/fatih-altayli/kose-yazilari';
    const DESCRIPTION = 'Fetches column articles from Fatih Altaylı';
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
        // The site has no RSS feed, so the listing page is scraped
        try {
            $html = getSimpleHTMLDOM(self::URI);
            if (!$html) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $fetchFullContent = $this->getInput('fetch_full_content');

        foreach ($html->find('a.blog-item') as $element) {
            // The page also lists "Otomobil Yazıları" cards below the pagination; keep only columns
            $subtitleElement = $element->find('span.blog-info-subtitle', 0);
            if (!$subtitleElement || trim($subtitleElement->plaintext) !== 'Köşe Yazıları') {
                continue;
            }

            $titleElement = $element->find('span.blog-info-title', 0);
            if (!$titleElement) {
                continue;
            }

            $item = [];

            $title = html_entity_decode(trim($titleElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $uri = urljoin(self::URI, $element->href);

            // Article URLs carry the publish date: /yazarlar/fatih-altayli/YYYY-MM-DD/slug
            if (preg_match('#/(\d{4}-\d{2}-\d{2})/#', $uri, $matches)) {
                $item['timestamp'] = strtotime($matches[1]);
            }

            // If "Fetch Full Content" is enabled, fetch the article content
            $contentHtml = '';
            if ($fetchFullContent) {
                try {
                    $articleHtml = getSimpleHTMLDOM($uri);
                    if (!$articleHtml) {
                        continue;
                    }

                    $contentElement = $articleHtml->find('div.detail-content', 0);
                    if ($contentElement) {
                        // Body starts with an empty <h2></h2> placeholder
                        foreach ($contentElement->find('h2') as $heading) {
                            if (trim($heading->plaintext) === '') {
                                $heading->outertext = '';
                            }
                        }
                        $contentHtml = $contentElement->innertext;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // Populate item data
            $item['title'] = 'Fatih Altaylı : ' . $title;
            $item['uri'] = $uri;
            $item['author'] = 'Fatih Altaylı';
            $item['content'] = $contentHtml;
            $item['uid'] = $uri;

            $this->items[] = $item;
        }
    }
}
