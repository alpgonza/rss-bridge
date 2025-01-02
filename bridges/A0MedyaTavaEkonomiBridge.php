<?php

class A0MedyaTavaEkonomiBridge extends BridgeAbstract {
    const NAME = 'Medya Tava Ekonomi Bridge';
    const URI = 'https://www.medyatava.com/ekonomi';
    const DESCRIPTION = 'Fetches articles from Medya Tava Economy page';
    const MAINTAINER = 'Algonza';
    const CACHE_TIMEOUT = 3600;

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI);

        // Loop through each article container on the main page
        foreach ($html->find('div.col-12.col-lg.mw0 div[class^="col-12"]') as $article) {
            $item = [];

            // Extract title
            $titleElement = $article->find('span', 0);
            if ($titleElement) {
                $item['title'] = $titleElement->plaintext;
            }

            // Extract URL
            $linkElement = $article->find('a', 0);
            if ($linkElement) {
                $item['uri'] = $linkElement->href;

                // Load linked article page
                $linkedHtml = getSimpleHTMLDOMCached($item['uri']);

                // Extract publication date
                $timeElement = $linkedHtml->find('time.pubdate', 0);
                if ($timeElement) {
                    $item['timestamp'] = strtotime($timeElement->datetime);
                }

                // Extract author
                $authorElement = $linkedHtml->find('span.source-name strong', 0);
                if ($authorElement) {
                    $item['author'] = $authorElement->plaintext;
                }
            }

            // Extract content (e.g., image alt text or placeholder text)
            // $contentElement = $article->find('img', 0);
            // if ($contentElement) {
            //    $item['content'] = $contentElement->alt;
            // }

            // Extract thumbnail
            $thumbnailElement = $article->find('picture source', 0);
            if ($thumbnailElement) {
                $item['enclosures'][] = strtok($thumbnailElement->srcset, ',');
            }

            // Assign unique ID
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}

