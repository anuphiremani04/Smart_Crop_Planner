<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__.'/db.php';
} catch(Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Load fetch function if file exists
if(file_exists(__DIR__ . '/fetch_google_news.php')) {
    require_once __DIR__ . '/fetch_google_news.php';
}

if(!function_exists('getRelativeDate')) {
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
}

try {
    $limit = isset($_GET['limit']) ? min(20, max(1, (int) $_GET['limit'])) : 10;
    $forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === 'true';
    
    // Check if we should fetch fresh news from Google
    $lastUpdate = null;
    $hoursSinceUpdate = null;
    
    try {
        $stmt = $pdo->query('SELECT MAX(published_date) as last_date FROM news_links');
        $lastRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if($lastRow && $lastRow['last_date']) {
            $lastUpdate = new DateTime($lastRow['last_date']);
            $now = new DateTime();
            $hoursSinceUpdate = ($now->getTimestamp() - $lastUpdate->getTimestamp()) / 3600;
        }
    } catch(Exception $e) {
        // Table might not exist or empty, that's okay
        error_log('News table check error: ' . $e->getMessage());
    }
    
    // Fetch fresh news from Google if forced or if last update was more than 6 hours ago
    if(($forceRefresh || !$lastUpdate || ($hoursSinceUpdate !== null && $hoursSinceUpdate > 6)) && function_exists('fetchGoogleNews')) {
        try {
            // Fetch from Google News
            $googleNews = fetchGoogleNews('agriculture farming Karnataka India', $limit);
            
            if($googleNews['success'] && !empty($googleNews['news'])) {
                // Save to database
                try {
                    // Clear old news (optional - keep last 50) - only if we have more than 50
                    $countStmt = $pdo->query('SELECT COUNT(*) as cnt FROM news_links');
                    $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                    if($countRow && $countRow['cnt'] > 50) {
                        $pdo->exec('DELETE FROM news_links WHERE news_id NOT IN (SELECT news_id FROM (SELECT news_id FROM news_links ORDER BY published_date DESC LIMIT 50) AS temp)');
                    }
                } catch(Exception $e) {
                    // Ignore if table doesn't support this query
                }
                
                // Insert news items, checking for duplicates by URL
                $insertStmt = $pdo->prepare('INSERT INTO news_links (title, excerpt, url, source, published_date) VALUES (?, ?, ?, ?, ?)');
                $checkStmt = $pdo->prepare('SELECT news_id FROM news_links WHERE url = ? LIMIT 1');
                
                foreach($googleNews['news'] as $item) {
                    try {
                        // Check if URL already exists
                        $checkStmt->execute([$item['url']]);
                        if($checkStmt->fetch()) {
                            continue; // Skip duplicates
                        }
                        
                        $insertStmt->execute([
                            $item['title'],
                            $item['excerpt'],
                            $item['url'],
                            $item['source'],
                            $item['published_date'] ?: date('Y-m-d')
                        ]);
                    } catch(Exception $e) {
                        // Skip duplicate or invalid entries
                        error_log('News insert error for item: ' . $e->getMessage());
                    }
                }
            } elseif($forceRefresh && !$googleNews['success']) {
                // If forced refresh failed, log but continue
                error_log('Google News fetch failed: ' . ($googleNews['error'] ?? 'Unknown error'));
            }
        } catch(Exception $e) {
            // If Google News fetch fails, continue with database news
            error_log('Google News fetch error: ' . $e->getMessage());
        }
    }
    
    // Get news from database
    try {
        $stmt = $pdo->prepare('
            SELECT news_id, title, excerpt, url, source, published_date 
            FROM news_links 
            ORDER BY published_date DESC, news_id DESC 
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no news in database, return empty array instead of error
        if(empty($news)) {
            ob_clean();
            echo json_encode(['success' => true, 'news' => [], 'count' => 0]);
            exit;
        }
        
        // Format dates and validate data
        $validNews = [];
        foreach($news as $item) {
            // Skip items without title (required field)
            if(empty($item['title']) || trim($item['title']) === '') {
                continue;
            }
            
            // Validate URL
            $url = trim($item['url'] ?? '');
            $isValidUrl = false;
            
            if(!empty($url)) {
                // Check if URL starts with http:// or https://
                if(preg_match('/^https?:\/\//', $url)) {
                    // Additional validation: check for valid domain (not placeholder domains)
                    $parsedUrl = parse_url($url);
                    if(isset($parsedUrl['host'])) {
                        $host = strtolower($parsedUrl['host']);
                        // Filter out placeholder/invalid domains
                        $invalidDomains = ['example.com', 'example.org', 'example.net', 'news.example', 'test.com', 'localhost'];
                        $isValidUrl = !in_array($host, $invalidDomains) && 
                                     !preg_match('/\.example$/', $host) &&
                                     filter_var($url, FILTER_VALIDATE_URL) !== false;
                    }
                }
            }
            
            // Ensure all fields have values
            $validItem = [
                'news_id' => $item['news_id'] ?? null,
                'title' => trim($item['title'] ?? 'No Title'),
                'excerpt' => trim($item['excerpt'] ?? 'No description available.'),
                'url' => $isValidUrl ? $url : '', // Only include valid URLs
                'source' => trim($item['source'] ?? 'News'),
                'published_date' => $item['published_date'] ?? null
            ];
            
            // Format dates
            if($validItem['published_date']) {
                try {
                    $date = new DateTime($validItem['published_date']);
                    $validItem['formatted_date'] = $date->format('F j, Y');
                    $validItem['relative_date'] = getRelativeDate($date);
                } catch(Exception $e) {
                    $validItem['formatted_date'] = 'Unknown';
                    $validItem['relative_date'] = 'Unknown';
                }
            } else {
                $validItem['formatted_date'] = 'Unknown';
                $validItem['relative_date'] = 'Unknown';
            }
            
            $validNews[] = $validItem;
        }
        
        ob_clean();
        echo json_encode(['success' => true, 'news' => $validNews, 'count' => count($validNews)]);
        
    } catch(Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    
} catch(Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>


