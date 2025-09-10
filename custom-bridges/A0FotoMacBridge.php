<?php

class A0FotoMacBridge extends FeedExpander {
	const NAME = 'Fotomac Basketbol';
	const URI = 'https://www.fotomac.com.tr';
	const DESCRIPTION = 'Basketball feed from fotomac.com.tr';
	const MAINTAINER = 'Alpgonza';
	const PARAMETERS = [];

	const FEED_URL = 'https://www.fotomac.com.tr/rss/basketbol.xml';

	public function collectData() {
        // Create a custom SSL context and set a browser-like User-Agent
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
            ]
        ]);

		// Fetch the RSS feed
        try {
            $xml = file_get_contents(self::FEED_URL, false, $context);
		if ($xml === false) {
                return; // Silently return to avoid error feed
            }
        } catch (Exception $e) {
            return; // Silently return to avoid error feed
		}

		// Parse the XML feed
        try {
            // Suppress warnings for malformed XML
            libxml_use_internal_errors(true);
            $feed = simplexml_load_string($xml);
            libxml_clear_errors();

		if ($feed === false) {
                return; // Silently return to avoid error feed
            }
        } catch (Exception $e) {
            return; // Silently return to avoid error feed
		}

		foreach ($feed->channel->item as $item) {
            // Validate required fields
            if (empty($item->link) || empty($item->title)) {
                continue; // Skip items with missing required fields
            }

			$this->items[] = [
				'uri' => (string)$item->link,
				'title' => (string)$item->title,
                'timestamp' => $item->pubDate ? strtotime((string)$item->pubDate) : null,
				'content' => (string)$item->description,
                'uid' => (string)$item->link, // Unique identifier
			];
		}
	}
}