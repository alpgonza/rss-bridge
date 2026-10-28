<?php
class A0ErtugrulOzkokYazilariBridge extends BridgeAbstract {
    const NAME = 'Ertuğrul Özkök Yazıları';
    const URI = 'https://10haber.net/yazarlar/ertugrul-ozkok/';
    const DESCRIPTION = 'Fetches articles from Ertuğrul Özkök on 10Haber';
    const MAINTAINER = 'Alpgonza';
    const PARAMETERS = [
        [
            'fetch_full_content' => [
                'name' => 'Fetch Full Content',
                'type' => 'checkbox',
                'title' => 'Enable to fetch full article content',
            ]
        ]
    ];
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        // The author feed only carries an excerpt, so full content is scraped from the article page
        try {
            // trim(): the feed is served with a leading newline before the XML declaration
            $rss = simplexml_load_string(trim(getContents(self::URI . 'feed/')));
            if (!$rss) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        $fetchFullContent = $this->getInput('fetch_full_content');

        foreach ($rss->channel->item as $rssItem) {
            $item = [];

            $uri = trim((string)$rssItem->link);
            $title = html_entity_decode(trim((string)$rssItem->title), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $timestamp = strtotime((string)$rssItem->pubDate);
            if ($timestamp !== false) {
                $item['timestamp'] = $timestamp;
            }

            // Feed excerpt starts with the post thumbnail; use it as enclosure
            $contentHtml = (string)$rssItem->description;
            if (preg_match('/<img[^>]+src="([^"]+)"/', $contentHtml, $matches)) {
                $item['enclosures'] = [$matches[1]];
            }

            // If "Fetch Full Content" is enabled, fetch the article content
            if ($fetchFullContent) {
                try {
                    $articleHtml = getSimpleHTMLDOM($uri);
                    if (!$articleHtml) {
                        continue;
                    }

                    $contentElement = $articleHtml->find('div#hbrconttr', 0);
                    if ($contentElement) {
                        // Inline images point to 300px thumbnails; use the largest srcset candidate instead
                        foreach ($contentElement->find('img') as $img) {
                            $srcset = $img->getAttribute('srcset');
                            if ($srcset) {
                                $best = $img->getAttribute('src');
                                $bestWidth = 0;
                                foreach (explode(',', $srcset) as $candidate) {
                                    $parts = preg_split('/\s+/', trim($candidate));
                                    $width = isset($parts[1]) ? (int)$parts[1] : 0;
                                    if ($width > $bestWidth) {
                                        $best = $parts[0];
                                        $bestWidth = $width;
                                    }
                                }
                                $img->setAttribute('src', $best);
                                $img->removeAttribute('srcset');
                                $img->removeAttribute('sizes');
                            }
                        }
                        $contentHtml = $contentElement->innertext;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // Populate item data
            $item['title'] = 'Ertuğrul Özkök : ' . $title;
            $item['uri'] = $uri;
            $item['author'] = 'Ertuğrul Özkök';
            $item['content'] = $contentHtml;
            $item['uid'] = $uri;

            $this->items[] = $item;
        }
    }
}
