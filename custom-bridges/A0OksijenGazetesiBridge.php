<?php
class A0OksijenGazetesiBridge extends BridgeAbstract {
    const NAME = 'Oksijen Gazetesi';
    const URI = 'https://gazeteoksijen.com';
    const DESCRIPTION = 'Returns latest articles from Gazete Oksijen';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        'Global' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Türkiye' => 'turkiye',
                    'Ekonomi' => 'ekonomi',
                    'Dünya' => 'dunya',
                    'Seyahat' => 'seyahat',
                    'Spor' => 'spor',
                    'Sağlık' => 'saglik'
                ],
                'defaultValue' => 'turkiye'
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

        // Track processed URLs to avoid duplicates
        $processedUrls = [];

        // Find articles in both layouts
        $articles = array_merge(
            $html->find('div[class*="col-12"] div[class*="card"]'),
            $html->find('div.col div[class*="card"]')
        );

        foreach ($articles as $article) {
            $item = [];

            // Get title and link
            $titleElement = $article->find('h2[class*="card-title"] a, h3[class*="card-title"] a, h5[class*="card-title"] a', 0);
            if (!$titleElement) continue;

            // Get the article URL and skip if already processed
            $articleUrl = $titleElement->href;
            if (!str_starts_with($articleUrl, 'https://')) {
                $articleUrl = self::URI . $articleUrl;
            }

            // Skip if we've already processed this URL
            if (in_array($articleUrl, $processedUrls)) {
                continue;
            }
            $processedUrls[] = $articleUrl;

            $item['uri'] = $articleUrl;
            $item['title'] = html_entity_decode(trim($titleElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Get thumbnail
            $imageElement = $article->find('div.card-image img', 0);
            if ($imageElement && isset($imageElement->src)) {
                $item['enclosures'] = [$imageElement->src];
                $item['thumbnail'] = $imageElement->src;
            }

            // Fetch article page content or only description and thumbnail
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

            // Get description
            $descElement = $articleHtml->find('div[class^="news__header"] div', 0);
            if ($descElement) {
                $description = html_entity_decode(trim($descElement->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $item['description'] = $description;

                // Prepare content
                $contentHtml = '<p>' . $description . '</p><br/>';
                if (isset($item['thumbnail'])) {
                    $contentHtml .= '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
                }

                if ($fetchContent) {
                    // Get article content
                    $contentElement = $articleHtml->find('article__content', 0);
                    if (!$contentElement) {
                        $contentElement = $articleHtml->find('article', 0); // Fallback to article without class
                    }
                    if ($contentElement) {
                        $contentHtml .= $contentElement->innertext;
                    }
                }

                $item['content'] = $contentHtml;
            }
            } catch (Exception $e) {
                // Skip this article if an HTTP error (e.g., 500) occurs
                // file_put_contents('bridge_errors.log', date('Y-m-d H:i:s') . ' - Error fetching article ' . $item['uri'] . ': ' . $e->getMessage() . "\n", FILE_APPEND);
                continue;
            }

            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        $category = $this->getInput('category');
        $categories = self::PARAMETERS['Global']['category']['values'];
        $categoryName = array_search($category, $categories);
        return "Oksijen Gazetesi" . ($categoryName ? " - {$categoryName}" : "");
    }
}