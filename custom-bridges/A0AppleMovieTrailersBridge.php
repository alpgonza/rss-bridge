<?php
class A0AppleMovieTrailersBridge extends BridgeAbstract {
    const NAME = 'Apple Movie Trailers';
    const URI = 'https://images.apple.com/trailers/home/rss/newtrailers.rss';
    const DESCRIPTION = 'Fetches the latest movie trailers from Apple Movie Trailers';
    const MAINTAINER = 'Alpgonza';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData() {
        $html = getSimpleHTMLDOM(self::URI) 
            or returnServerError('Could not load the main page');
    
        // Parse all ".canvas-lockup" items
        foreach ($html->find('.infinite-grid__body .canvas-lockup') as $item) {
            $this->items[] = $this->parseCanvasLockup($item);
        }
    
        // Parse only the first 5 ".shelf-grid__list-item" items
        $shelfItems = $html->find('.shelf-grid__list .shelf-grid__list-item');
        $limit = min(5, count($shelfItems)); // Limit to 5 items or fewer if fewer are available
        for ($i = 0; $i < $limit; $i++) {
            $this->items[] = $this->parseShelfGridItem($shelfItems[$i]);
        }
    }
    
    // Helper function to parse ".canvas-lockup" items
    private function parseCanvasLockup($item) {
        $title = $item->find('a', 0)->attr['aria-label'] ?? '';
        $uri = 'https://tv.apple.com/' . ($item->find('a', 0)->href ?? '');
        $thumbnail = '';
        if ($source = $item->find('source', 0)) {
            $srcset = $source->attr['srcset'];
            $thumbnail = strtok(strtok($srcset, ','), ' ');
        }
    
        return [
            'title' => htmlspecialchars_decode($title, ENT_QUOTES),
            'uri' => $uri,
            'content' => htmlspecialchars_decode($title, ENT_QUOTES),
            'enclosures' => [$thumbnail],
            'uid' => $uri
        ];
    }
    
    // Helper function to parse ".shelf-grid__list-item" items
    private function parseShelfGridItem($item) {
        $title = $item->find('a', 0)->attr['aria-label'] ?? '';
        $uri = 'https://tv.apple.com/' . ($item->find('a', 0)->href ?? '');
        $thumbnail = '';
        if ($source = $item->find('source', 0)) {
            $srcset = $source->attr['srcset'];
            $thumbnail = strtok(strtok($srcset, ','), ' ');
        }
    
        return [
            'title' => htmlspecialchars_decode($title, ENT_QUOTES),
            'uri' => $uri,
            'content' => htmlspecialchars_decode($title, ENT_QUOTES),
            'enclosures' => [$thumbnail],
            'uid' => $uri
        ];
    }
    
}

