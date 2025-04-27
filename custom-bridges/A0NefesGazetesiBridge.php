<?php

class A0NefesGazetesiBridge extends BridgeAbstract {
    const NAME = 'Nefes Gazetesi';
    const URI = 'https://www.nefes.com.tr';
    const DESCRIPTION = 'Returns the latest articles from Nefes Gazetesi';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        'Global' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Gündem' => 'gundem',
                    'Ekonomi' => 'ekonomi',
                    'Dünya' => 'dunya'
                ],
                'defaultValue' => 'gundem'
            ],
            'fetch_content' => [
                'name' => 'Fetch full article content',
                'type' => 'checkbox',
                'defaultValue' => true
            ]
        ]
    ];

    public function collectData() {
        $category = $this->getInput('category');
        $fetchContent = $this->getInput('fetch_content');
        $url = self::URI . '/' . $category;

        // Fetch the main page
        try {
            $html = getSimpleHTMLDOM($url);
            if (!$html) {
                // Silently return if the main page couldn't be fetched
                return;
            }
        } catch (Exception $e) {
            // Log the error for debugging (optional)
            // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching main page ' . $url . ': ' . $e->getMessage() . "\n", FILE_APPEND);
            return; // Silently return to avoid error feed
        }

        foreach ($html->find('article[class*="card"]') as $element) {
            $item = [];

            // Get link and title
            $linkElement = $element->find('a', 0);
            if (!$linkElement) continue;

            $item['uri'] = $linkElement->href;
            if (!str_starts_with($item['uri'], 'https://')) {
                $item['uri'] = self::URI . $item['uri'];
            }
            $item['title'] = $linkElement->title;

            // Get thumbnail
            $thumbnailElement = $element->find('img', 0);
            if ($thumbnailElement && isset($thumbnailElement->src)) {
                $item['enclosures'] = [$thumbnailElement->src];
                $item['thumbnail'] = $thumbnailElement->src;
            }

            if ($fetchContent) {
                // Full fetch logic
                try {
                    $articleHtml = getSimpleHTMLDOM($item['uri']);
                if (!$articleHtml) {
                    // Skip this article if the content could not be fetched
                    continue;
                }

                // Check for SSL or cURL errors
                if (isset($articleHtml->innertext) && 
                    (strpos($articleHtml->innertext, 'error') !== false || 
                     strpos($articleHtml->innertext, 'SSL') !== false || 
                     strpos($articleHtml->innertext, 'cURL') !== false)) {
                    continue; // Skip if the page contains error information
                }

                if ($category === 'yazarlar') {
                    $authorElement = $articleHtml->find('div.author-name h1', 0);
                    if ($authorElement) {
                        $author = html_entity_decode(trim($authorElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $item['author'] = $author;
                        $item['title'] = $author . ' : ' . $item['title'];
                    }
                } else {
                    $authorElement = $articleHtml->find('div.post-reporter', 0);
                    if ($authorElement) {
                        $item['author'] = html_entity_decode(trim($authorElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }

                $dateElement = $articleHtml->find('div.post-time time', 0);
                if ($dateElement) {
                    $item['timestamp'] = strtotime($dateElement->datetime);
                }

                $descElement = $articleHtml->find('h2', 0);
                $contentElement = $articleHtml->find('div.post-content', 0);
                if ($contentElement) {
                    foreach ($contentElement->find('div[class*="related-news"], div[class*="adpro"]') as $unwanted) {
                        $unwanted->outertext = '';
                    }

                    $contentHtml = '';
                    if ($descElement) {
                        $description = html_entity_decode(trim($descElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $item['description'] = $description;
                        $contentHtml = '<p><strong>' . $description . '</strong></p><br/>';
                    }

                    if (isset($item['thumbnail'])) {
                        $contentHtml .= '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
                    }

                    $contentHtml .= $contentElement->innertext;
                    $item['content'] = $contentHtml;
                }
                } catch (Exception $e) {
                    // Skip this article if an HTTP error (e.g., 500) occurs
                    // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $item['uri'] . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                    continue;
                }
            } else {
                // Fetch only description and thumbnail when checkbox is unchecked
                try {
                    $articleHtml = getSimpleHTMLDOM($item['uri']);
                    if (!$articleHtml) {
                        // Skip this article if the content could not be fetched
                        continue;
                    }

                    // Check for SSL or cURL errors
                    if (isset($articleHtml->innertext) &&
                        (strpos($articleHtml->innertext, 'error') !== false ||
                         strpos($articleHtml->innertext, 'SSL') !== false ||
                         strpos($articleHtml->innertext, 'cURL') !== false)) {
                        continue; // Skip if the page contains error information
                    }

                    $descElement = $articleHtml->find('header h2', 0);
                    if ($descElement) {
                        $description = html_entity_decode(trim($descElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $item['description'] = $description;

                        // Set content as description + thumbnail
                        $contentHtml = '<p>' . $description . '</p><br/>';
                        if (isset($item['thumbnail'])) {
                            $contentHtml .= '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
                        }
                        $item['content'] = $contentHtml;
                    }
                } catch (Exception $e) {
                    // Skip this article if an HTTP error (e.g., 500) occurs
                    // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $item['uri'] . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                    continue;
                }
            }

            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        $category = $this->getInput('category');
        $categories = self::PARAMETERS['Global']['category']['values'];
        $categoryName = array_search($category, $categories);
        return "Nefes Gazetesi" . ($categoryName ? " - {$categoryName}" : "");
    }
}