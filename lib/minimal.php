<?php
require_once('log.inc.php');
require_once('var.inc.php');
require_once('cache.inc.php');
require_once('scrud.inc.php');
require_once('html.inc.php');

sql_connect();

print file_cache('homePage', '+1 second', function () {

	$html = '';

	block('header');
	print menu(array('Aller au contenu' => '#content', 'Rechercher' => '#search'));
	print menu(array('Accueil' => '/', 'Fonctionnalités', 'Documentation' => '/api', 'Téléchargements' => '/download', 'A propos' => '/about'));
	print search(); // connecter a un index de recherche (cache des critères courants ? vues ?)
	$html .= block_end(false);

	block('content');
	print cms('A la une'); // affiche un éditeur de texte pour laisser la possibilité à l'utilisateur d'éditer du contenu
	print datalist('news', array('limit' => 3));
	$html .= block_end(false);

	block('footer');
	print menu(array('Copyright - ' . date('Y'), hyperlink('Me contacter', 'mailto:robin.ruaux@gmail.com'), 'Mentions légales' => '/legals'));
	$html .= block_end(false);


	$html = html5(array(
		'title' => 'Page d\'accueil',
		'body' => $html));

	return $html;
});

?>