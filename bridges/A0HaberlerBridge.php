<?php

class A0HaberlerBridge extends BridgeAbstract {
    const NAME = 'Haberler.com';
    const URI = 'https://www.haberler.com/';
    const DESCRIPTION = 'Generates RSS feeds with full content for Haberler.com articles';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const PARAMETERS = array();

    public function collectData() {
        ini_set('default_socket_timeout', 30);
        
        $rssFeedUrl = 'https://rss.haberler.com/rss.asp';
        $rssContent = getSimpleHTMLDOM($rssFeedUrl)
            or returnServerError('Could not fetch Haberler.com RSS feed');

        foreach ($rssContent->find('item') as $itemElement) {
            $item = [];

            // Get title
            $title = $itemElement->find('title', 0)->innertext;
            $item['title'] = trim(html_entity_decode(str_replace(['<![CDATA[', ']]>'], '', $title)));

            // Get URL from guid
            $guid = $itemElement->find('guid', 0)->innertext;
            $item['uri'] = trim(str_replace(['<![CDATA[', ']]>'], '', $guid));

            // Get publication date
            $pubDate = $itemElement->find('pubDate', 0)->plaintext;
            $item['timestamp'] = strtotime($pubDate);

            // Get image from media:content
            $mediaContent = $itemElement->find('media:content', 0);
            $imageHtml = '';
            if ($mediaContent && isset($mediaContent->url)) {
                $imageUrl = $mediaContent->url;
                $item['enclosures'][] = $imageUrl;
                $imageHtml = '<img src="' . $imageUrl . '" /><br>';
            }

            // Get description as fallback content
            $description = $itemElement->find('description', 0)->innertext;
            $item['content'] = trim(html_entity_decode(str_replace(['<![CDATA[', ']]>'], '', $description)));

            // Fetch full article content
            if ($item['uri']) {
                $articlePage = getSimpleHTMLDOM($item['uri']);
                if ($articlePage) {
                    // Get author from editorSade div
                    $editorDiv = $articlePage->find('div.editorSade', 0);
                    if ($editorDiv) {
                        $authorText = $editorDiv->plaintext;
                        if (strpos($authorText, ' /') !== false) {
                            $item['author'] = trim(substr($authorText, 0, strpos($authorText, ' /')));
                        }
                    }

                    // Get the entire haber_metni content
                    $articleContent = $articlePage->find('[class*=haber_metni]', 0);
                    if ($articleContent) {
                        // Fix lazy-loaded images
                        foreach ($articleContent->find('img[data-src]') as $img) {
                            $img->src = $img->getAttribute('data-src');
                            $img->removeAttribute('data-src');
                            $img->removeAttribute('data-sanitized-class');
                            $img->removeAttribute('class');
                        }
                        
                        $item['content'] = $imageHtml . $articleContent->innertext;
                    }
                }
            }

            $this->items[] = $item;
        }
    }
}

