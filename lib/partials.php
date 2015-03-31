<?php
require_once('partial.class.php');

// Tag HTML
$tag = new Partial('Tag générique', '<{tag} {attrs}>{content}</{tag}>', array('tag', 'content', 'attrs'));

// Javascript
$scriptExternal = new Partial('Fichier Javascript', '<script type="text/javascript" src="{src}"></script>', array('src' => ''));
$script = new Partial('Javascript', '<script type="text/javascript" src="{src}"></script>', array('src' => ''));

// CSS
$styleExternal = new Partial('Fichier CSS', '<link rel="stylesheet" type="text/css" href="{src}" media="{media}" />', array('src' => '', 'media' => 'all'));
$style = new Partial('Style CSS', '<style>{style}</style>', array('style' => ''));

// Liens
$link = new Partial('Lien hypertexte', '<a href="{href}"><span>{name}</span></a>', array('name' => '', 'href' => '#'));
$linkIconic = new Partial('Lien avec icône', '<a href="{href}"><span class="{class}"></span>', array('class' => '', 'href' => '#'));

// Images
$image = new Partial('Image', '<img src="{image}" alt="{alt}" width="{width}" height="{height}" />');

// Fonts
$googleFont = new Partial('Google WebFont', '<link href="https://fonts.googleapis.com/css?family={font}" rel="stylesheet" type="text/css" />');

// Formulaires
$label = new Partial('Label', '<label for="{for}">{label}</label>', array('label' => '', 'for' => ''));

$inputCheckbox = new Partial('Checkbox', '<input type="checkbox" name="{name}" id="{id}" value={value} {checked} />{text}', array('text' => '', 'name' => '', 'value' => '', 'checked' => ''));
$inputRadio = new Partial('Bouton radio', '<input type="radio" name="{name}" id="{id}" value="{value}" {checked} />', array('name' => '', 'id' => '', 'value' => '', 'checked' => ''));
$inputText = new Partial('Champ texte', '<input type="text" name="{name}" id="{id}" value="{value}" placeholder="{placeholder}" maxlength="{maxlength}" />', array('name' => '', 'value' => '', 'id' => '', 'placeholder' => '', 'maxlength' => ''));
$inputTextArea = new Partial('Champ texte multiligne', '<input type="textarea" name="{name}" id="{id}" placeholder="{placeholder}" maxlength="{maxlength}">{value}</textarea>', array('name' => '', 'value' => '', 'id' => '', 'placeholder' => '', 'maxlength' => ''));
$inputSubmit = new Partial('Bouton d\'envoi de formulaire', '<input type="submit" name="{name}" id="{id}" value="{label}" />', array('name' => 'btnSubmit', 'label' => 'OK', 'id' => ''));

// Template HTML 5
$page = new Partial('Template HTML 5', '<!DOCTYPE html>
<html>
	<head>
		<title>{title}</title>
		<meta charset="{encoding}" />
		<meta name="description" content="{description}" />
		<meta name="keywords" content="{keywords}" />
		<meta name="viewport" content="width=device-width,initial-scale=1.0">
		
		{webfonts}
		{stylesheets}
	</head>

	<body>
		{body}
		{scripts}
	</body>

</html>
', array('title' => '', 'encoding' => 'UTF-8', 'description' => '', 'keywords' => '', 'body' => '', 'stylesheets' => '', 'scripts' => '', 'webfonts' => ''));

// Menus
$menu = new Partial('Menu de navigation', '
	<ul class="menu {class}">
		{items}
	</ul>
', array('items' => '', 'class' => ''));

$item = new Partial('élément de menu', '<li>{item}</li>');

// Formulaire de recherche
$formSearch = new Partial('Formulaire de recherche', '
	<form id="{id}" method="{method}" action="{action}" role="search">
		' . $label->using(array('Rechercher', 'input-search')) . '
		' . $inputText->using(array('search', '', 'input-search', '{placeholder}')) . '
		' . $inputSubmit->using(array('OK')) . '
	</form>
	', array('id', 'action', 'method' => 'GET', 'label' => 'Rechercher', 'placeholder' => '')
);