<?php
class A0ParamedyaYazarlarBridge extends BridgeAbstract {
    const NAME = 'Yazarlar';
    const URI = 'https://www.paramedya.com/';
    const DESCRIPTION = 'Fetches articles from selected authors on Paramedya.';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Select Author',
                'type' => 'list',
                'values' => [
                    'Remzi Özdemir' => 'haberler/yazarlar/remziozdemir',
                    'Ayhan Bülent TOPTAŞ' => 'devami/author/ayhanbulenttoptas',
                    'Burak Özdoğan' => 'devami/author/burakozdogan',
                    'C.Ertuğrul SADIKOĞLU' => 'haberler/yazarlar/ertugrul-sadikoglu',
                    'Hanife Fişek' => 'haberler/yazarlar/hanifeserter',
                    'Sait Ürünlü' => 'devami/author/saiturunlu',
                    'Soner Gökten' => 'haberler/yazarlar/prof-dr-soner-gokten',
                    'Şubeci' => 'haberler/yazarlar/subeci',
                    'Türker Açıkgöz' => 'devami/author/turkeracikgoz',
                ]
            ]
        ]
    ];

    public function getName() {
        $category = $this->getInput('category');
        $categoryNames = array_flip(self::PARAMETERS[0]['category']['values']);
        return 'Paramedya - ' . (isset($categoryNames[$category]) ? $categoryNames[$category] : self::NAME);
    }

    public function collectData() {
        $category = $this->getInput('category');
        $url = self::URI . $category;
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('article[class^="jeg_post"]') as $element) {
            $item = [];
            $titleElement = $element->find('h2.jeg_post_title a', 0) ?? $element->find('h3.jeg_post_title a', 0);
            if (!$titleElement) {
                continue; // Skip if title not found
            }
            $item['uri'] = $titleElement->href;
            $item['title'] = $titleElement->plaintext;
            $item['author'] = $element->find('div.jeg_meta_author a', 0)->plaintext ?? '';

            $articleHtml = getSimpleHTMLDOM($item['uri']);
            $description = $articleHtml->find('h2.jeg_post_subtitle', 0);
            $articleText = $articleHtml->find('div[class^="content-inner"] p');

            $item['content'] = ($description ? $description->plaintext . '<br>' : '');
            foreach ($articleText as $paragraph) {
                $item['content'] .= $paragraph->outertext;
            }

            $this->items[] = $item;
        }
    }
}
