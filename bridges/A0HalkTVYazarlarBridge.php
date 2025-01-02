<?php
class A0HalkTVYazarlarBridge extends BridgeAbstract {
    const NAME = 'Halk TV Yazarlar Bridge';
    const URI = 'https://halktv.com.tr/yazar';
    const DESCRIPTION = 'Fetches articles from Halk TV Yazarlar';
    const MAINTAINER = 'Alpgonza';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request ' . self::URI);

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

            // Fetch the article page to get the date
            $articleHtml = getSimpleHTMLDOM($uri);
            if ($articleHtml) {
                $dateElement = $articleHtml->find('div.content-date time', 0);
                $timestamp = $dateElement ? $dateElement->datetime : null;
                if ($timestamp) {
                    $item['timestamp'] = strtotime($timestamp);
                }
            }

            // Extract thumbnail
            $thumbnailElement = $element->find('img', 0);
            $thumbnail = $thumbnailElement ? $thumbnailElement->src : '';

            // Populate item data
            $item['title'] = $author . ' : ' . $title;
            $item['uri'] = $uri;
            $item['author'] = $author;
            $item['enclosures'] = [$thumbnail];
            $item['uid'] = $uri;

            $this->items[] = $item;
        }
    }
}

