<?php
class A0ErtugrulOzkokYazilariBridge extends BridgeAbstract {
    const NAME = 'Ertuğrul Özkök Yazıları';
    const URI = 'https://bizimtv.com.tr/yazarlar/ertugrul-ozkok';
    const DESCRIPTION = 'Fetches articles from Ertuğrul Özkök on BizimTV';
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
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $fetchFullContent = $this->getInput('fetch_full_content');

        // Find all articles in the article list section
        foreach ($html->find('section.article-list article.item') as $element) {
            $item = [];

            // Extract title and URL
            $linkElement = $element->find('a', 0);
            if (!$linkElement) {
                continue;
            }
            $title = trim($linkElement->plaintext);
            $uri = $linkElement->href;
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                $uri = 'https://bizimtv.com.tr' . $uri;
            }

            // Extract date
            $dateElement = $element->find('span.date', 0);
            $dateStr = $dateElement ? trim($dateElement->plaintext) : '';
            if ($dateStr) {
                // Parse Turkish date format (e.g., "06 Eylül 2025 Cumartesi 23:08")
                if (preg_match('/(\d{2})\s+(Ocak|Şubat|Mart|Nisan|Mayıs|Haziran|Temmuz|Ağustos|Eylül|Ekim|Kasım|Aralık)\s+(\d{4})\s+(?:Pazartesi|Salı|Çarşamba|Perşembe|Cuma|Cumartesi|Pazar)\s+(\d{2}):(\d{2})/', $dateStr, $matches)) {
                    $day = $matches[1];
                    $month = array_search($matches[2], [
                        'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
                        'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'
                    ]) + 1;
                    $year = $matches[3];
                    $hour = $matches[4];
                    $minute = $matches[5];
                    
                    $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
                    if ($timestamp !== false) {
                        $item['timestamp'] = $timestamp;
                    }
                }
            }

            // If "Fetch Full Content" is enabled, fetch the article content
            $contentHtml = '';
            if ($fetchFullContent) {
                try {
                    $articleHtml = getSimpleHTMLDOM($uri);
                    if (!$articleHtml) {
                        continue;
                    }

                    // Extract article content
                    $contentElement = $articleHtml->find('article.article-content', 0);
                    if ($contentElement) {
                        $contentHtml = $contentElement->innertext;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // Populate item data
            $item['title'] = 'Ertuğrul Özkök : ' . $title;
            $item['uri'] = $uri;
            $item['author'] = 'Ertuğrul Özkök';
            $item['content'] = $fetchFullContent ? $contentHtml : '';
            $item['uid'] = $uri;

            $this->items[] = $item;
        }
    }
}