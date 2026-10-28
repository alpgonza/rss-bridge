<?php
class A0NefesYazarlarBridge extends BridgeAbstract {
    const NAME = 'Nefes Yazarlar';
    const URI = 'https://www.nefes.com.tr/yazarlar';
    const DESCRIPTION = 'Returns the latest articles from Nefes writers';
    const MAINTAINER = 'Alpgonza';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [];

    // The site pages are behind a Cloudflare challenge; only the export feed is reachable
    const FEED_URI = 'https://www.nefes.com.tr/export/rss/yazarlar';

    // The feed has no author field, so names come from the URL slug.
    // Slugs lose Turkish characters; known writers are spelled correctly here.
    const AUTHORS = [
        'alican-ozcan' => 'Alican Özcan',
        'argun-darici' => 'Argun Darıcı',
        'aytunc-erkin' => 'Aytunç Erkin',
        'can-atakli' => 'Can Ataklı',
        'damla-turgutlu-soybas' => 'Damla Turgutlu Soybaş',
        'deniz-zeyrek' => 'Deniz Zeyrek',
        'devrim-demirel' => 'Devrim Demirel',
        'fatih-soylemezoglu' => 'Fatih Söylemezoğlu',
        'memduh-bayraktaroglu' => 'Memduh Bayraktaroğlu',
        'murat-muratoglu' => 'Murat Muratoğlu',
        'nevsin-mengu' => 'Nevşin Mengü',
        'nuray-babacan' => 'Nuray Babacan',
        'soner-yalcin' => 'Soner Yalçın',
        'umit-zileli' => 'Ümit Zileli',
        'utkan-aydin' => 'Utkan Aydın',
    ];

    public function collectData() {
        // Fetch the feed
        try {
            $rss = simplexml_load_string(trim(getContents(self::FEED_URI)));
            if (!$rss) {
                return;
            }
        } catch (Exception $e) {
            return; // Silently return to avoid error feed
        }

        foreach ($rss->channel->item as $rssItem) {
            $item = [];

            $item['uri'] = trim((string)$rssItem->link);

            // Author slug: /yazarlar/{slug}/{article}
            $author = '';
            if (preg_match('#/yazarlar/([^/]+)/#', $item['uri'], $matches)) {
                $slug = $matches[1];
                $author = self::AUTHORS[$slug] ?? ucwords(str_replace('-', ' ', $slug));
            }

            $title = html_entity_decode(trim((string)$rssItem->title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $item['title'] = $author ? $author . ' : ' . $title : $title;
            $item['author'] = $author;

            $timestamp = strtotime((string)$rssItem->pubDate);
            if ($timestamp !== false) {
                $item['timestamp'] = $timestamp;
            }

            $description = (string)$rssItem->description;

            // Author photo; request 600x338 instead of the feed's 150x84
            $contentHtml = '';
            if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
                $imageUrl = str_replace('/Cw150h84q95gc/', '/Cw600h338q95gc/', $matches[1]);
                $item['enclosures'] = [$imageUrl];
                $contentHtml = '<img src="' . $imageUrl . '" /><br/><br/>';
            }

            // Excerpt is double-encoded (e.g. &amp;ouml;), so decode twice
            if (preg_match('#<h4>(.*?)</h4>#s', $description, $matches)) {
                $excerpt = html_entity_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $contentHtml .= '<p>' . htmlspecialchars(trim($excerpt)) . '</p>';
            }

            $item['content'] = $contentHtml;

            // Set unique ID using article URL
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
