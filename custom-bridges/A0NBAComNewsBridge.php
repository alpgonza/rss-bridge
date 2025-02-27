<?php
class A0NBAComNewsBridge extends BridgeAbstract {
    const NAME = 'NBA.com News';
    const URI = 'https://www.nba.com/news';
    const DESCRIPTION = 'Fetches the latest news articles from NBA.com';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request NBA.com');

        foreach ($html->find('div.NewsView_dazn__ZF2K2 li') as $element) {
            $item = [];

            // Extract title
            $titleElement = $element->find('span.MultiLineEllipsis_ellipsis___1H7z', 0);
            $item['title'] = $titleElement ? $titleElement->plaintext : 'No title';

            // Extract URL link
            $linkElement = $element->find('a', 0);
            $item['uri'] = $linkElement ? urljoin(self::URI, $linkElement->href) : '';

            // Extract thumbnail
            $imgElement = $element->find('img', 0);
            $thumbnail = $imgElement ? $imgElement->src : '';

            // Extract description (second span)
            $descElement = $element->find('span.MultiLineEllipsis_ellipsis___1H7z', 1);
            $description = $descElement ? $descElement->plaintext : 'No description available';

            // Fetch author and publish date from the linked page
            if (!empty($item['uri'])) {
                $articleHtml = getSimpleHTMLDOM($item['uri']) or returnServerError('Could not load article page');

                // Extract author
                $authorElement = $articleHtml->find('p.ArticleAuthor_authorName___AnQD', 0);
                $item['author'] = $authorElement ? $authorElement->plaintext : 'Unknown author';

                // Extract publication date
                $dateElement = $articleHtml->find('time.ArticleHeader_ahDate__J3fwr', 0);
                $item['timestamp'] = $dateElement ? strtotime($dateElement->plaintext) : time();
            }

            // Combine description + thumbnail for content
            $content = '<p>' . $description . '</p>';
            if ($thumbnail) {
                $content .= '<img src="' . $thumbnail . '">';
            }
            $item['content'] = $content;

            $this->items[] = $item;
        }
    }
}
