<?php

class A0MedyaTavaBridge extends BridgeAbstract {
    const NAME = 'Medya Tava';
    const URI = 'https://www.medyatava.com/';
    const DESCRIPTION = 'Generates RSS feeds for Medya Tava';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const PARAMETERS = [
        'Global' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Gündem' => 'gundem',
                    'Ekonomi' => 'ekonomi'
                ],
                'defaultValue' => 'gundem'
            ],
            'full_content' => [
                'name' => 'Fetch full article content',
                'type' => 'checkbox',
                'defaultValue' => false
            ]
        ]
    ];

    public function collectData() {
        $category = $this->getInput('category');
        $fullContent = $this->getInput('full_content');
        $url = self::URI . $category;
        
        $html = getSimpleHTMLDOM($url)
            or returnServerError('Could not fetch Medya Tava content');

        foreach ($html->find('div.col-12.col-lg.mw0 div[class^="col-12"]') as $element) {
            $item = [];

            // Extract title
            $title = $element->find('span', 0);
            $item['title'] = $title ? $title->plaintext : 'No title available';

            // Extract URI
            $link = $element->find('a', 0);
            $item['uri'] = $link ? urljoin($url, $link->href) : $url;

            // Extract thumbnail
            $thumbnailSource = $element->find('picture source', 0);
            $thumbnail = '';
            if ($thumbnailSource) {
                $srcset = $thumbnailSource->attr['srcset'];
                $thumbnail = strtok($srcset, ',');
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

                    if ($fullContent) {
                        // Extract full article content
                        $contentDetail = $linkedPage->find('div.content-detail', 0);
                        if ($contentDetail) {
                            // Remove unwanted elements
                            foreach ($contentDetail->find('h1, nav, a.google-news_wrapper, figure, div.info, div.sharing, div[class^="adpro"], aside, a[class^="box"], div[class^="col-6"]') as $unwanted) {
                                $unwanted->outertext = '';
                            }
                            
                            // Prepend thumbnail to content if available
                            $contentHtml = ($thumbnail ? '<img src="' . $thumbnail . '" alt="Thumbnail"><br>' : '') . $contentDetail->innertext;
                            $item['content'] = $contentHtml;
                        } else {
                            $item['content'] = 'No content available';
                        }
                    } else {
                        // Use simple content (image alt text)
                        $imageAlt = $element->find('img', 0);
                        $item['content'] = $imageAlt ? str_replace('"', '', $imageAlt->alt) : 'No content available';
                    }
                }
            }

            // Use URI as a unique identifier
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }

    public function getName() {
        $category = $this->getInput('category');
        $categories = self::PARAMETERS['Global']['category']['values'];
        $categoryName = array_search($category, $categories);
        return "Medya Tava Haberler" . ($categoryName ? " - {$categoryName}" : "");
    }
}
