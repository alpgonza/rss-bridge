<?php
class A0TennisComNewsBridge extends BridgeAbstract {
    const NAME = 'Tennis.com News';
    const URI = 'https://www.tennis.com/news/all-news/';
    const DESCRIPTION = 'Returns latest news articles from Tennis.com';
    const MAINTAINER = 'Algonza';
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI)
            or returnServerError('Could not request Tennis.com');

        // Find articles under div with class "d3-l-col__col-4"
        foreach ($html->find('div.d3-l-col__col-4') as $article) {
            $item = [];

            // Get article link and title
            $linkElement = $article->find('a', 0);
            if (!$linkElement) continue;

            $item['uri'] = urljoin(self::URI, $linkElement->href);
            $item['title'] = trim($linkElement->title);

            // Get author
            $authorElement = $article->find('small.fa-text__meta span', 0);
            $item['author'] = $authorElement ? trim($authorElement->plaintext) : 'Unknown';

            // Get description from <p> inside div.fa-text__body
            $descElement = $article->find('div.fa-text__body p', 0);
            $description = $descElement ? trim($descElement->plaintext) : 'No description available';

            // Get thumbnail image from <img> inside <picture>
            $imgElement = $article->find('div.fm-card__media picture img', 0);
            if ($imgElement && isset($imgElement->attr['data-src'])) {
                $thumbnail = $imgElement->getAttribute('data-src');
            } elseif ($imgElement && isset($imgElement->src)) {
                $thumbnail = $imgElement->src;
            } else {
                $thumbnail = 'https://www.tennis.com/default-thumbnail.jpg'; // Fallback
            }

            $item['enclosures'] = [$thumbnail];
            $item['thumbnail'] = $thumbnail;

            // Prepare content with description followed by thumbnail
            $item['content'] = '<p>' . $description . '</p><img src="' . $thumbnail . '" />';

            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }

    public function getName() {
        return 'Tennis.com News';
    }
}
