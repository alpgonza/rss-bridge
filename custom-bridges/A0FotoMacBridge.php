<?php

class A0FotoMacBridge extends FeedExpander {

	const NAME = 'Fotomac Basketbol';
	const URI = 'https://www.fotomac.com.tr';
	const DESCRIPTION = 'Basketball feed from fotomac.com.tr';
	const MAINTAINER = 'your-name';
	const PARAMETERS = [];

	const FEED_URL = 'https://www.fotomac.com.tr/rss/basketbol.xml';

	public function collectData() {
		$context = stream_context_create([
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true,
			],
		]);

		$xml = file_get_contents(self::FEED_URL, false, $context);

		if ($xml === false) {
			returnServerError('Could not fetch feed.');
		}

		$feed = simplexml_load_string($xml);

		if ($feed === false) {
			returnServerError('Could not parse XML.');
		}

		foreach ($feed->channel->item as $item) {
			$this->items[] = [
				'uri' => (string)$item->link,
				'title' => (string)$item->title,
				'timestamp' => strtotime((string)$item->pubDate),
				'content' => (string)$item->description,
			];
		}
	}
}
