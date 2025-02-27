<?php
class A0NefesYazarlarBridge extends BridgeAbstract {
    const NAME = 'Nefes Yazarlar';
    const URI = 'https://www.nefes.com.tr/yazarlar';
    const DESCRIPTION = 'Returns the latest articles from Nefes writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [];

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request ' . self::URI);

        foreach ($html->find('section[1] article') as $element) {
            $item = [];

            // Get article title and URL from the second <a> element
            $titleElement = $element->find('a.card-content--title', 0);
            if (!$titleElement) continue;

            // Get author from span inside the link
            $authorElement = $titleElement->find('span', 0);
            if (!$authorElement) continue;

            // Get the article URL
            $item['uri'] = $titleElement->href;
            if (!str_starts_with($item['uri'], 'https://')) {
                $item['uri'] = 'https://www.nefes.com.tr' . $item['uri'];
            }

            // Get title from title attribute and author from span
            $item['title'] = $authorElement->plaintext . ' : ' . $titleElement->title;
            $item['author'] = trim($authorElement->plaintext);

            // Get thumbnail if available
            $thumbnailElement = $element->find('img', 0);
            if ($thumbnailElement && isset($thumbnailElement->src)) {
                $item['enclosures'] = [$thumbnailElement->src];
                $item['thumbnail'] = $thumbnailElement->src;
            }

            // Fetch the article page to get the date and content
            $articleHtml = getSimpleHTMLDOM($item['uri']);
            if ($articleHtml) {
                // Get date from the card-content time element
                $dateElement = $articleHtml->find('div.post-time time', 0);
                if ($dateElement) {
                    $item['timestamp'] = strtotime($dateElement->datetime);
                }

                // Get article content
                $contentElement = $articleHtml->find('div.post-content', 0);
                if ($contentElement) {
                    // Start content with thumbnail
                    $contentHtml = '';
                    if (isset($item['thumbnail'])) {
                        $contentHtml = '<img src="' . $item['thumbnail'] . '" /><br/><br/>';
                    }
                    
                    // Add article content
                    $contentHtml .= $contentElement->innertext;
                    $item['content'] = $contentHtml;
                }
            }

            // Set unique ID using article URL
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
