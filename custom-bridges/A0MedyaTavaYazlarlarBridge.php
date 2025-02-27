<?php
class A0MedyaTavaYazlarlarBridge extends BridgeAbstract {
    const NAME = 'Medya Tava Yazarlar';
    const URI = 'https://www.medyatava.com/yazarlar';
    const DESCRIPTION = 'Returns the latest articles from Medyatava writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [];

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request ' . self::URI);

        foreach ($html->find('div.author-item') as $element) {
            $item = [];

            // Get article title and author
            $titleElement = $element->find('a.article-title', 0);
            $authorElement = $element->find('a.name', 0);

            if (!$titleElement || !$authorElement) continue;

            // Get the article URL
            $item['uri'] = $titleElement->href;
            if (!str_starts_with($item['uri'], 'https://')) {
                $item['uri'] = 'https://www.medyatava.com' . $item['uri'];
            }
            
            // Combine author and title
            $item['title'] = $authorElement->title . ' : ' . $titleElement->plaintext;
            
            // Set author
            $item['author'] = $authorElement->title;

            // Get thumbnail if available
            $thumbnailElement = $element->find('source', 0);
            if ($thumbnailElement) {
                $item['enclosures'] = [$thumbnailElement->src];
            }

            // Fetch the article page to get the date
            $articleHtml = getSimpleHTMLDOM($item['uri']);
            if ($articleHtml) {
                // Get date from time element
                $dateElement = $articleHtml->find('time.pubdate', 0);
                if ($dateElement && isset($dateElement->datetime)) {
                    $item['timestamp'] = strtotime($dateElement->datetime);
                }
                
                // Get the article content
                $contentElement = $articleHtml->find('div.content-text', 0);
                if ($contentElement) {
                    $item['content'] = $contentElement->plaintext;
                }
            }

            // Set unique ID using article URL
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
