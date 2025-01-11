<?php
class A0OksijenGazetesiBridge extends BridgeAbstract {
    const NAME = 'Oksijen Gazetesi Bridge';
    const URI = 'https://gazeteoksijen.com/';
    const DESCRIPTION = 'Returns latest articles from Gazete Oksijen';
    const MAINTAINER = 'Your Name';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        'Category' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Türkiye' => 'turkiye',
                    'Ekonomi' => 'ekonomi',
                    'Dünya' => 'dunya',
                    'Sağlık' => 'saglik'
                ],
                'defaultValue' => 'turkiye'
            ]
        ]
    ];

    public function collectData() {
        $category = $this->getInput('category');
        $url = self::URI . $category;
        
        $html = getSimpleHTMLDOM($url);

        foreach($html->find('div.divider-y-3.divider-y-lg-4 > div.card.card-horizontal') as $article) {
            $item = [];
            
            // Get title
            $titleElement = $article->find('h5.card-title a', 0);
            $item['title'] = trim($titleElement->plaintext);
            $item['uri'] = $titleElement->href;
            
            // Get image
            $imageElement = $article->find('img', 0);
            $item['enclosures'] = [$imageElement->src];
            
            // Get description
            $descElement = $article->find('p.line-clamp-3', 0);
            $item['content'] = $descElement->plaintext;
            
            // Get date
            $dateElement = $article->find('span.fs-7', 0);
            $item['timestamp'] = strtotime($dateElement->plaintext);
            
            // Get author
            $authorElement = $article->find('div.d-flex span.d-block', 0);
            $item['author'] = trim($authorElement->plaintext);
            
            $this->items[] = $item; 
    }
    }
    
    public function getName() {
        $category = $this->getInput('category');
        $categories = self::PARAMETERS['Category']['category']['values'];
        $categoryName = array_search($category, $categories);
        return "Oksijen Gazetesi" . ($categoryName ? " - {$categoryName}" : "");
    }
}
