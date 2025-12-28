<?php
// Function to fetch news from Google News RSS
function fetchGoogleNews($query = 'agriculture farming India', $limit = 10) {
    $news = [];
    
    try {
        // Google News RSS feed URL
        $rssUrl = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=en-IN&gl=IN&ceid=IN:en';
        
        // Try to fetch RSS feed using cURL first (more reliable)
        $rssContent = false;
        
        if(function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $rssUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $rssContent = @curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if($httpCode !== 200 || $rssContent === false) {
                $rssContent = false;
            }
        }
        
        // Fallback to file_get_contents if cURL failed
        if($rssContent === false && ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'follow_location' => true,
                    'max_redirects' => 5
                ]
            ]);
            
            $rssContent = @file_get_contents($rssUrl, false, $context);
        }
        
        // Try alternative URL if first attempt failed
        if($rssContent === false) {
            $altUrl = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=en&gl=IN&ceid=IN:en';
            
            if(function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $altUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $rssContent = @curl_exec($ch);
                curl_close($ch);
            } elseif(ini_get('allow_url_fopen')) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 10,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'follow_location' => true,
                        'max_redirects' => 5
                    ]
                ]);
                $rssContent = @file_get_contents($altUrl, false, $context);
            }
        }
        
        if($rssContent === false || empty($rssContent)) {
            return ['success' => false, 'error' => 'Failed to fetch news feed. Please check your internet connection or server configuration.'];
        }
        
        // Parse RSS XML
        $xml = @simplexml_load_string($rssContent);
        
        if($xml === false) {
            return ['success' => false, 'error' => 'Failed to parse news feed'];
        }
        
        $count = 0;
        foreach($xml->channel->item as $item) {
            if($count >= $limit) break;
            
            $title = (string)$item->title;
            $link = (string)$item->link;
            $pubDate = (string)$item->pubDate;
            $description = (string)$item->description;
            
            // Clean up description (remove HTML tags)
            $description = strip_tags($description);
            $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
            $excerpt = mb_substr($description, 0, 200);
            if(mb_strlen($description) > 200) {
                $excerpt .= '...';
            }
            
            // Extract source from description or title
            $source = 'Google News';
            if(preg_match('/- ([^-]+)$/', $title, $matches)) {
                $source = trim($matches[1]);
            }
            
            // Parse date
            $date = null;
            if($pubDate) {
                $date = date('Y-m-d', strtotime($pubDate));
            }
            
            $news[] = [
                'title' => $title,
                'excerpt' => $excerpt,
                'url' => $link,
                'source' => $source,
                'published_date' => $date,
                'formatted_date' => $date ? date('F j, Y', strtotime($date)) : 'Unknown',
                'relative_date' => $date ? getRelativeDate(new DateTime($date)) : 'Unknown'
            ];
            
            $count++;
        }
        
        return ['success' => true, 'news' => $news, 'count' => count($news)];
        
    } catch(Exception $e) {
        return ['success' => false, 'error' => 'Error fetching news: ' . $e->getMessage()];
    }
}

function getRelativeDate($date) {
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if($diff->days == 0) {
        return 'Today';
    } elseif($diff->days == 1) {
        return 'Yesterday';
    } elseif($diff->days < 7) {
        return $diff->days . ' days ago';
    } elseif($diff->days < 30) {
        $weeks = floor($diff->days / 7);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif($diff->days < 365) {
        $months = floor($diff->days / 30);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff->days / 365);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}
?>

