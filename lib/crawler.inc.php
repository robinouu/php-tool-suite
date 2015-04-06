<?php

require_once('core.inc.php');
require_once('url.inc.php');

function crawler_get_url($url, $isSSL = null){
	$metas = array();

	if( strlen($url) > 2048 ){
		return null;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	var_dump($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Minimal Framework default user agent (' . guid() . ')');
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
	curl_setopt($ch, CURLOPT_NOSIGNAL, 1); 
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 4500);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$isSSL);
	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

$already_visited = array();

function crawler_load_sitemap($sitemapURL, $checkDomain = null, $cb = null) {
	$content = crawler_get_url($sitemapURL);
	$urls = array();

	$base_url = url_website($checkDomain);
	if( $content ){

		$sitemap = @simplexml_load_string($content);
		if( !$sitemap ){
			return array();
		}

		foreach ($sitemap as $node) {

			$route = (string)$node->loc;
			
			$site_url = url_website($route);

			$route = substr($route, strlen($site_url));
			
			if( ($nodeName = strtolower($node->getName())) === 'url' ){
				//var_dump($site_url, $base_url);
				if( !$node->loc || $site_url !== $base_url ){
					continue;
				}
				$attrs = array();
				if( $node->lastmod ){
					$attrs['lastmod'] = strtotime($node->lastmod);
				}
				if( $node->changefreq ){
					$attrs['changefreq'] = (string)$node->changefreq;
				}
				if( $node->priority ){
					$attrs['priority'] = (float)$node->priority;
				}
				
				$urls[$route] = $attrs;



				if( is_callable($cb) ){
					$cb($route, $attrs);
				}

			}elseif( $nodeName === 'sitemap' ){
				$urls = array_merge($urls, crawler_load_sitemap($site_url . $route, $checkDomain, $cb));
			}
		}
	}
	return $urls;
}


function crawler_crawl_site($siteFirstLevelDomain, $callbackFoundURL) {

	$site = array('external_links' => array());
	$urls = crawler_load_sitemap('http://'. $siteFirstLevelDomain . '/sitemap.xml', 'http://'. $siteFirstLevelDomain, $callbackFoundURL);

	// check the website links
	$base_url = 'http://'. $siteFirstLevelDomain . '/';
	$content = crawler_get_url(utf8_encode($base_url));
	$dom = dom($content);
	if( !$dom ){
		return $site;
	}
	foreach ($dom->find('a') as $link) {
		if( preg_match('/https?:\/\/(.*)/', $link->href, $m) ){
			$parsed = parse_url($m[0]);
			if( isset($parsed['host']) ){
				$site['external_links'][] = $parsed['host'];
			}
		}
	}
	foreach ($dom->find('meta') as $meta) {
		$metalower = strtolower($meta->name);
		$content = substr(html_entity_decode($meta->content), 0, 255);
		if( $metalower === 'author' ){
			$site['author'] = $content;
		}elseif( $metalower === 'description' ){
			$site['description'] = $content;
		}elseif( $metalower === 'keywords' ){
			$site['keywords'] = $content;
		}
	}
	return $site;
}


function crawler_web($urls) {
	global $already_visited;

	$newurls = array();
	foreach ($urls as $url) {
		if( in_array($url, $already_visited))
			continue;

		$content = crawler_get_url($url);
		$already_visited[] = $url;

		$words = preg_split("#[\s'\.;,:\/\(\)\[\]]#", $content);
		$words = array_unique($words);

		if (preg_match_all("#<a(.*)href=\"(.*)\"(.*)>(.+)<\/a>#ui", $content, $m)) {
			foreach ($m as $match) {
				$newurls[] = $match[2];
				
			}
		}
	}

	if (sizeof($newurls))
		crawl_web($newurls);
}
