<?php
/**
 * This package maintains a suitable crawler for your needs.
 * You can get the result of an URL using crawler\_get\_url and crawler\_post\_url for basic operations.
 * 
 * Advanced browsing of website can also be done using crawler\_get\_page\_info and crawler\_crawl\_site
 * 
 * An example of a website crawler :
 * 
 * ```php
 * crawler_crawl_site('http://www.wikipedia.com', function ($route, $page){
 * 	print $route . ' : ' . $page['title'] . "<br />";
 * });
 * ```
 * &nbsp;
 * @package php-tool-suite
 * @subpackage Crawler
 */

plugin_require(array('var', 'request'));

var_set('crawler/HTTPCodeWhiteList', array(200, 201, 202, 203, 205, 210));
var_set('crawler/fileExtBlackList', array('jpg', 'jpeg', 'bmp', 'png', 'gif', 'tar', 'gz', 'zip', 'xml', 'pdf', 'rar'));
var_set('crawler/keywordsBlackList', array());

/**
 * Gets the content of an URL
 * @param string $url The URL to browse
 * @param array $headers An array of HTTP headers
 * @return string The content of the page
 * @subpackage Crawler
 */
function crawler_get_url($url, $headers = array()){
	$metas = array();

	if( strlen($url) > 2048 ){
		return null;
	}

	$isSSL = server_is_secure();
	if( substr($url, 0, 5) == 'https' ){
		$isSSL = true;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, guid());
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
	//curl_setopt($ch, CURLOPT_NOSIGNAL, 1); 
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

/**
 * Gets the content of an URL using POST method
 * 
 * @param string $url The URL to browse
 * @param type $datas The data to pass on the HTTP request
 * @return string The content of the page
 * @subpackage Crawler
 */
function crawler_post_url($url, $datas){
	$metas = array();

	if( strlen($url) > 2048 ){
		return null;
	}

	$datasStr = '';
	foreach ($datas as $k => $d) {
		if( is_array($d) ){	
			foreach ($d as $vd) {
				$datasStr .= $k.'='.urlencode($vd).'&';
			}
		}else
			$datasStr .= $k.'='.urlencode($d).'&';
	}
	$datasStr = rtrim($datasStr, '&');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, guid());
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
	//curl_setopt($ch, CURLOPT_NOSIGNAL, 1); 
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 4500);
	curl_setopt($ch, CURLOPT_POST, sizeof($datas));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $datasStr);
	
	$result = curl_exec($ch);

	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if( !in_array($code, var_get('crawler/HTTPCodeWhiteList')) ){
		return false;
	}
	curl_close($ch);

	$__BOM = pack('CCC', 239, 187, 191);
	// Careful about the three ='s -- they're all needed.
	while(0 === strpos($result, $__BOM))
		$content = substr($result, 3);
	$result = utf8_encode($result);

	return $result;
}

$already_visited = array();

/**
 * Gets all URLs from a website sitemap.
 * 
 * The method handles also links of multiple sitemap files.
 * 
 * @param string $sitemapURL The sitemap URL
 * @param int $maxPages The max number of pages to load
 * @return array An array of indexed URLs.
 * @subpackage Crawler
 */
function crawler_load_sitemap($sitemapURL, $maxPages = 20) {
	$content = crawler_get_url($sitemapURL);
	$urls = array();

	$base_url = url_website($sitemapURL);
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
				if( !trim($route) ){
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

/**
 * Gets meta information about a website page.
 * @param string $url The website URL
 * @return array An array of meta informations about the page : 
 * 'external_links' : an array of links that are not on the same domain
 * 'internal_links' : an array of links that are on the same domain
 * 'content' : the HTML content of the page
 * 'title' : the <title /> tag of the page
 * @subpackage Crawler
 */
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
	plugin_require('html');
	$dom = dom($content);
	
	if( !$dom ){
		return $page;
	}

	$page['dom'] = $dom;
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
	//$page['content'] = '';
	$page['content'] = $dom;
 	$page['title'] = $dom->find('title');
	$page['title'] = sizeof($page['title']) ? trim($page['title'][0]->plaintext) : null;
	
	if( $page['title'] === null ){
		$page['title'] = $dom->find('h1');
		$page['title'] = sizeof($page['title']) ? trim($page['title'][0]->plaintext) : null;
	}
	/*
	foreach( $dom->find('#mw-content-text > *') as $txtContent ) {
		if( $txtContent->tag === 'p') {
			$c = trim($txtContent->plaintext);
			$page['content'] .= $c . ' ';
		}
	}*/

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


/**
 * Loads all site webpages using sitemap interrogation.
 * @param string $siteFirstLevelDomain The root URL of the site to load.
 * @param callable $callbackFoundURL The callback to use when page loaded.
 * @subpackage Crawler
 */
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



function crawler_crawl_web($options=array()){

	$options = array_merge(array(
		'startDomain' => 'https://www.wikipedia.org',
		'dataPath' => 'data/',
		'dumpRate' => 10,
		'onPageContent' => null,
		'onBrowseDomain' => null,
		'onDump'=> null,
	), $options);

	$nbDomains = 0;

	touch($options['dataPath'].'domains.json');
	touch($options['dataPath'].'domainsVisited.json');

	if( !isset($domainsToVisit) || sizeof($domainsToVisit) === 0 ){
		$domainsToVisit = json_decode(file_get_contents($options['dataPath'].'domains.json'), true);
		$domainsToVisit = (array)$domainsToVisit;
		if( !sizeof($domainsToVisit) ){
			$domainsToVisit[] = $options['startDomain'];
		}
	}

	if( !isset($domainsVisited) || sizeof($domainsVisited) === 0 ){
		$domainsVisited = json_decode(file_get_contents($options['dataPath'].'domainsVisited.json'), true);
		$domainsVisited = (array)$domainsVisited;
	}

	while (1) {	

		$copyDomains = $domainsToVisit;
		$newDomains = array();

		foreach ($copyDomains as $key => $domain) {
			
			if( is_callable($options['onBrowseDomain']) ){
				$browse = $options['onBrowseDomain']($domain);
				if( !$browse ){
					continue;
				}
			}
			$domainsVisited[] = $domain;
			unset($domainsToVisit[$key]);

			$page = crawler_get_page_info($domain);
			$back = true;
			if( is_callable($options['onPageContent']) ){
				$back = $options['onPageContent']($domain, $page);
			}
			if( sizeof($page['external_links']) ){
				assoc_array_shuffle($page['external_links']);
				$newDomains = array_merge($newDomains, $page['external_links']);
			}

			if( $nbDomains % $options['dumpRate'] === 0 || $nbDomains == 0 ) {
				print 'dumping...';

				if( is_callable($options['onDump']) ){
					$options['onDump']($newDomains);
				}

				$domainsToVisit = array_merge($domainsToVisit, $newDomains);
				$domainsToVisit = array_unique($domainsToVisit);
				$newDomains = array();

				file_put_contents($options['dataPath'].'domains.json', json_encode($domainsToVisit));
				file_put_contents($options['dataPath'].'domainsVisited.json', json_encode($domainsVisited));

				print 'done' . PHP_EOL;
			}

			// soulage le serveur
			sleep(0.01);
			++$nbDomains;
		}
	}

}
