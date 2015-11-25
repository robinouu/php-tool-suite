<?php
/**
 * Crawler
 * @package php-tool-suite
 */

plugin_require(array('var', 'request'));

var_set('crawler/HTTPCodeWhiteList', array(200, 201, 202, 203, 205, 210));
var_set('crawler/fileExtBlackList', array('jpg', 'jpeg', 'bmp', 'png', 'gif', 'tar', 'gz', 'zip', 'xml', 'pdf', 'rar'));
var_set('crawler/keywordsBlackList', array());



function crawler_get_url($url, $headers = array(), $isSSL = null){
	$metas = array();

	if( strlen($url) > 2048 ){
		return null;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Tool Suite (' . guid() . ')');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
	curl_setopt($ch, CURLOPT_NOSIGNAL, 1); 
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 4500);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$isSSL);
	if( sizeof($headers) ){
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

	$result = curl_exec($ch);

	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if( !in_array($code, var_get('crawler/HTTPCodeWhiteList')) ){
		return false;
	}
	curl_close($ch);

	return $result;
}

$already_visited = array();

function crawler_load_sitemap($sitemapURL, $checkDomain = null, $maxPages = 20) {
	$content = crawler_get_url($sitemapURL);
	$urls = array();

	$base_url = url_website($checkDomain);
	if( $content ){

		$sitemap = @simplexml_load_string($content);
		if( !$sitemap ){
			return array();
		}

		foreach ($sitemap as $node) {
			
			if( --$maxPages == 0 ){
				break;
			}

			$route = (string)$node->loc;
			
			$site_url = url_website($route);

			$route = substr($route, strlen($site_url));

			$nodeName = strtolower($node->getName());
			if( $nodeName === 'url' ){
				if( !trim($route) || $site_url !== $base_url ){
					continue;
				}
				$attrs = array();
				if( $node->lastmod ){
					$attrs['lastmod'] = (string)($node->lastmod);
				}
				if( $node->changefreq ){
					$attrs['changefreq'] = (string)$node->changefreq;
				}
				if( $node->priority ){
					$attrs['priority'] = (float)$node->priority;
				}
				
				$urls[$route] = $attrs;

			}elseif( $nodeName === 'sitemap' ){
				$urls = array_merge($urls, crawler_load_sitemap($site_url . $route, $checkDomain, $maxPages));
			}
		}
	}
	return $urls;
}

function crawler_get_page_info($url){
	$url = utf8_encode($url);
	$content = crawler_get_url($url);

	$__BOM = pack('CCC', 239, 187, 191);
	// Careful about the three ='s -- they're all needed.
	while(0 === strpos($content, $__BOM))
		$content = substr($content, 3);

	$content = utf8_encode($content);
	$base_url = parse_url($url);

	$page = array();
	$page['external_links'] = array();
	$page['internal_links'] = array();

//	$content = strip_tags($content, '<html>,<body>,<p>,<div>,<a>');
	$dom = dom($content);
	return $dom;
	
	if( !$dom ){
		return $page;
	}
	
	foreach ($dom->find('a') as $link) {
		$link->href = htmlspecialchars($link->href);

		if( preg_match('/^https?:\/\/(.*)/', $link->href, $m) ){
			$parsed = parse_url($m[0]);

			if( isset($parsed['host'], $base_url['host']) && $parsed['host'] === $base_url['host'] ){
				$ext = explode('.', $link->href);
				$ext = $ext[sizeof($ext)-1];
				if( in_array($ext, var_get('crawler/fileExtBlackList')) ){
					continue;
				}
				$page['internal_links'][] = preg_replace('/^(https?):\/\/' . preg_quote($parsed['host']) . '/', '', $link->href, 1);
			}else{
				$page['external_links'][] = $parsed['host'];
			}
		}elseif( isset($base_url['host']) && preg_match('#^\/(.*)#', $link->href, $m) ){
			$page['internal_links'][] = $m[0];
		}
	}

	$page['internal_links'] = array_unique($page['internal_links']);
	$page['external_links'] = array_unique($page['external_links']);
	$page['content'] = '';
	$page['title'] = $dom->find('title');
	$page['title'] = sizeof($page['title']) ? trim($page['title'][0]->plaintext) : null;
	
	if( $page['title'] === null ){
		$page['title'] = $dom->find('h1');
		$page['title'] = sizeof($page['title']) ? trim($page['title'][0]->plaintext) : null;
	}
	
	foreach( $dom->find('#mw-content-text > *') as $txtContent ) {
		if( $txtContent->tag === 'p') {
			$c = trim($txtContent->plaintext);
			$page['content'] .= $c . ' ';
		}
	}

	/*$words = array_map('trim', preg_split('#[\s\.;,:\(\)\[\]\"\'\!\?\/\-]#', substr($page['content'], 0, 10000)));
	$occ = array();

	$averageWordLength = 5.5;
	$blacklist = var_get('crawler/keywordsBlackList');
	foreach ($words as $word) {
		$word = strtolower($word);
		if( in_array($word, $blacklist) || preg_match('#[^a-zéçèàù]#', $word) || !$word ){
			continue;
		}
		$averageWordLength += strlen($word);
		$averageWordLength /= 2;

		if( !isset($occ[$word]) ){
			$occ[$word] = 1;
		}else{
			++$occ[$word];
		}
	}
	
	arsort($occ);
	$topWords = array_slice($occ, 0, 15);

	foreach ($dom->find('meta') as $meta) {
		$metalower = strtolower($meta->name);
		$content = substr(html_entity_decode($meta->content), 0, 255);
		if( $metalower === 'author' ){
			$page['author'] = $content;
		}elseif( $metalower === 'description' ){
			$page['description'] = $content;
		}elseif( $metalower === 'keywords' ){
			$page['keywords'] = $content;
		}
	}

	if( !isset($page['keywords']) ){
		$page['keywords'] = implode(',', array_keys($topWords));
	}

	// Use this as final website score
	$page['score'] = $averageWordLength;
*/
	return $page;
}

function crawler_crawl_site($siteFirstLevelDomain, $callbackFoundURL) {

	$site = array('pages' => array());
	

	// check the website links
	$base_url = 'http://'. $siteFirstLevelDomain;
	
	$sitemapURLs = crawler_load_sitemap('http://'. $siteFirstLevelDomain . '/sitemap.xml', 'http://'. $siteFirstLevelDomain);
	$routes = array_keys($sitemapURLs);
	if( !$routes || !sizeof($routes) ){
		$routes = array('/');
	}
	$already_visited = array();

	$maxPages = 5;
	do {
		$diff = array_diff($routes, $already_visited);
		if( sizeof($diff) === 0 ){
			break;
		}
		foreach ($diff as $route) {
			if( --$maxPages == 0 ){
				break;
			}
			$page = crawler_get_page_info($base_url . $route);
			if( isset($sitemapURLs[$route]) ){
				$page = array_merge($page, $sitemapURLs[$route]);

			}

			$already_visited[] = $route;

			$routes = array_unique(array_merge($routes, $page['internal_links']));

			$callbackFoundURL($route, $page);
		}
	} while ( sizeof($diff) && $maxPages > 0 );


	$site['pages'][] = $page;
	return $site;
}
