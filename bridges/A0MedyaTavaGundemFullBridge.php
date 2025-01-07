<?php

class A0MedyaTavaGundemFullBridge extends BridgeAbstract {
    const NAME = 'Medya Tava Gündem Full Bridge';
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

            // Extract URI
            $link = $element->find('a', 0);
            $item['uri'] = $link ? urljoin(self::URI, $link->href) : self::URI;

            // Extract thumbnail
            $thumbnailSource = $element->find('picture source', 0);
            $thumbnail = '';
            if ($thumbnailSource) {
                $srcset = $thumbnailSource->attr['srcset'];
                $thumbnail = strtok($srcset, ','); // First item before the comma
                $item['enclosures'][] = $thumbnail;
            }

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

                    // Extract full article content
                    $contentDetail = $linkedPage->find('div.content-detail', 0);
                    if ($contentDetail) {
                        // Remove unwanted elements
                        foreach ($contentDetail->find('h1, nav, a.google-news_wrapper, figure, div.info, div[class^="adpro"], aside, div[class^="col-6"]') as $unwanted) {
                            $unwanted->outertext = ''; // Remove element
                        }

                        // Prepend thumbnail to content if available
                        $contentHtml = ($thumbnail ? '<img src="' . $thumbnail . '" alt="Thumbnail"><br>' : '') . $contentDetail->innertext;

                        $item['content'] = $contentHtml;
                    } else {
                        $item['content'] = 'No content available';
                    }
                }
            }

            // Use URI as a unique identifier
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}

