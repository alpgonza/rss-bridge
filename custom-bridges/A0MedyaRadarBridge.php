<?php
class A0MedyaradarBridge extends BridgeAbstract {
    const NAME = 'Medya Radar';
    const URI = 'https://www.medyaradar.net';
    const DESCRIPTION = 'Fetches the latest articles from Medyaradar.net';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
       'Global' => [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Gündem' => '/gundem',
                    'Politika' => '/politika',
                    'Magazin' => '/magazin',
                    'Televizyon' => '/televizyon',
                    'Diziler' => '/diziler',
                    'Ekonomi' => '/ekonomi',
                    'Sağlık' => '/saglik',
                    'Reyting' => '/reyting',
                    'Kültür-Sanat' => '/kultur-sanat',
                    'Polemik-Kulis' => '/polemik-kulis'
                ],
                'defaultValue' => '/gundem'
            ]
        ]
    ];

    public function collectData() {
        $category = $this->getInput('category');
        $url = self::URI . $category;
        $html = getSimpleHTMLDOM($url) or returnServerError('Could not request Medyaradar.net');

        foreach ($html->find('div.col-12.col-md-4') as $element) {
            $item = [];

            // Extract the article link
            $linkElement = $element->find('a', 0);
            $item['uri'] = $linkElement ? urljoin(self::URI, $linkElement->href) : '';

            // Extract the article title
            $item['title'] = $linkElement ? $linkElement->title : 'No title';

            // Extract the thumbnail
            $pictureElement = $element->find('picture', 0);
            $thumbnail = $pictureElement ? $pictureElement->{'data-iesrc'} : '';

            // Fetch the article page for the description
            $description = '';
            if ($item['uri']) {
                $articleHtml = getSimpleHTMLDOM($item['uri']) or returnServerError('Could not load article page');
                $headerElements = $articleHtml->find('header');
                if (isset($headerElements[1])) {
                    $descriptionElement = $headerElements[1]->find('h2', 0);
                    $description = $descriptionElement ? $descriptionElement->innertext : '';
                }
            }

            // Content will include description + thumbnail order
            $content = '';
            if ($description) {
                $content .= '<p>' . html_entity_decode(htmlspecialchars($description)) . '</p>';
            }
            if ($thumbnail) {
                $content .= '<img src="' . html_entity_decode(htmlspecialchars($thumbnail)) . '" alt="' . html_entity_decode(htmlspecialchars($item['title'])) . '">';
            }

            $item['content'] = $content;

            $this->items[] = $item;
        }
    }
    public function getName() {
        $category = $this->getInput('category');
        $categories = self::PARAMETERS['Global']['category']['values'];
        $categoryName = array_search($category, $categories);
        return "Medya Radar" . ($categoryName ? " - {$categoryName}" : "");
    }
}
