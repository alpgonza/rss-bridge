<?php

class A0MedyaTavaGundemBridge extends BridgeAbstract {
    const NAME = 'Medya Tava Gündem Bridge';
    const URI = 'https://www.medyatava.com/gundem';
    const DESCRIPTION = 'Generates RSS feeds for Medya Tava';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not fetch Medya Tava content');

        // Loop through the list of articles
        foreach ($html->find('div.col-12.col-lg.mw0 div[class^="col-12"]') as $element) {
            $item = [];

            // Extract title
            $title = $element->find('span', 0);
            $item['title'] = $title ? $title->plaintext : 'No title available';

            // Extract content (using alt text of the image, removing quotes)
            $imageAlt = $element->find('img', 0);
            $item['content'] = $imageAlt ? str_replace('"', '', $imageAlt->alt) : 'No content available';

            // Extract URI
            $link = $element->find('a', 0);
            $item['uri'] = $link ? urljoin(self::URI, $link->href) : self::URI;

            // Fetch details from the linked article
            if ($item['uri']) {
                $linkedPage = getSimpleHTMLDOM($item['uri']);
                if ($linkedPage) {
                    // Extract publish date
                    $pubDateElement = $linkedPage->find('time.pubdate', 0);
                    if ($pubDateElement) {
                        $item['timestamp'] = strtotime($pubDateElement->datetime);
                    }

                    // Extract author
                    $authorElement = $linkedPage->find('span.source-name strong', 0);
                    if ($authorElement) {
                        $item['author'] = $authorElement->plaintext;
                    }
                }
            }

            // Extract thumbnail
            $thumbnailSource = $element->find('picture source', 0);
            if ($thumbnailSource) {
                $srcset = $thumbnailSource->attr['srcset'];
                $item['enclosures'][] = strtok($srcset, ','); // First item before the comma
            }

            // Use URI as a unique identifier
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
