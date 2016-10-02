<?php

plugin_require('field', 'html');

class Webapp {
	public $routes = array();
	public function run(){
		foreach ($this->routes as $key => $value) {
			route($key, $value);
		}
		trigger('routing');
	}
}


class Webpage extends Widget {
	public function __construct($html=''){
		parent::__construct($html);
		$this->metas = array();
		$this->title = '';
		$this->charset = 'utf-8';
		$this->lang = current_lang() ? current_lang() : 'fr';
	}
	public function render(){
		
		$styles = $this->styles;
		$scripts = $this->scripts;
		$this->browseChildren(function ($widg) use (&$styles, &$scripts) {
			$styles = array_merge($styles, $widg->styles);
			$scripts = array_merge($scripts, $widg->scripts);
		});

		$metas = tag('meta', '', array('charset' => $this->charset), true).'
		';
		foreach ($this->metas as $key => $content) {
			$metas .= tag('meta', '', array('name' => $key, 'content' => $content), true).'
		';
		}

		$this->before = '<!DOCTYPE html>
<html lang="'.$this->lang.'" xml:lang="'.$this->lang.'">
	<head>
		<title>'.$this->title.'</title>
		'.$metas.'
		'.implode('
		', $styles).'
	</head>
	<body>
		';
		$this->after = '
		'.implode('
		', $scripts).'
	</body>
</html>';

		return parent::render();
	}
}

class Slider extends Widget {
	public function __construct(){
		parent::__construct();

		$this->scripts['jquery'] = javascript(array('src' => 'https://code.jquery.com/jquery-2.2.4.min.js'));
		$this->scripts['slick'] = javascript(array('src' => '//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js')) . javascript(array(), '
$(document).ready(function(){
	$(".slider").slick();
});');
		$this->styles['slick'] = stylesheet(array('href' => '//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css'));
		$this->before = '<div class="slider">';
		$this->after = '</div>';
	}
}

class Form extends Widget {
	public $fields = array();
	public $errors = array();
	public function __construct($html=''){
		parent::__construct($html);
		$this->class = 'form';
		$this->name = 'form';
		$this->onValidate = null;
		$this->title = '';
		$this->btnLabel = __('OK');
	}
	public function validate(){
		if( isset($_REQUEST['btn'.ucfirst($this->name)]) ){
			$validated = true;
			on('error', function ($e) { $this->errors[] = $e; });
			$datas = array();
			foreach ($this->children as $k => &$f) {
				$obj = new ReflectionClass(get_class($f));
				if( get_class($f) == 'FileField' ){
					$value = $_FILES[$k];
					$f->attributes['name'] = $k;
					$validated = $validated && $f->validate($value);
					$datas[$k] = $value['name'];
				}elseif( $obj->isSubclassOf('Field') && isset($_REQUEST[$k]) ){
					$f->attributes['name'] = $k;
					$validated = $validated && $f->validate($_REQUEST[$k]);
					$datas[$k] = $_REQUEST[$k];
				}
			}
			if( is_callable($this->onValidate) )
				$this->onValidate->__invoke($validated, $datas);
			off('error');
		}
	}
	protected function displayErrors(){
		$html = '<ul>';
		foreach ($this->errors as $e) {
			if( isset($e['msg']) ){
				$html .= $e['msg'];
			}else{
				$html .= '<li>Le champ ' . $e['context'] . ' est incorrect. Veuillez le rectifier.</li>';
			}
		}
		$html .= '</ul>';
		return $html;
	}
	public function render(){
		$content = '';
		if( isset($this->before) ){
			$content .= $this->before;
		}
		if( $this->title )
			$content .= title($this->title, 2);
		$content .= (sizeof($this->errors) ? $this->displayErrors() : '' );
		foreach ($this->children as $k => &$widg) {
			$obj = new ReflectionClass(get_class($widg));
			if( $obj->isSubclassOf('Field') ){
				$widg->attributes['name'] = $k;
				$widg->attributes['value'] = isset($_REQUEST[$k]) ? $_REQUEST[$k] : (isset($widg->attributes['value']) ? $widg->attributes['value'] : null);
			}
			if( get_class($widg) == 'BooleanField' ){
				$widg->attributes['checked'] = isset($_REQUEST[$k]) ? true : (isset($widg->attributes['value']) ? $widg->attributes['value'] : false);
			}
			$content .= $widg->render();
		}
		$content .= '<input type="submit" class="btn" name="btn'.ucfirst($this->name).'" value="'.(isset($this->btnLabel) ? $this->btnLabel : '').'" />';
		if( isset($this->after) ){
			$content .= $this->after;
		}
		return tag('form', $content, array('method' => 'POST', 'class' => $this->class, 'enctype' => 'multipart/form-data'));
	}
}


class Menu extends Widget {
	public $items = array();
	public function __construct($items=array()){
		parent::__construct();
		$this->items = $items;
		$this->class = '';
		$this->styles['icons'] = stylesheet(array('href' => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css'));
		$this->styles['Menu'] = stylesheet(array(), '
.menu {
	display: block;
	margin: 0;
	padding: 0;
}
.menu li {
	display: inline-block;
	vertical-align: middle;
	margin: 0;
	padding: 0;
}
.menu a:hover {
	text-decoration: none;
}');
	}
	public function toHTML(){
		$html = '<ul class="menu '.$this->class.'">';
		foreach ($this->items as $key => $value) {
			$value = array_merge(array('label' => '', 'href' => '#', 'icon' => ''), $value);
			$html .= '<li><a href="'.$value['href'].'" class="'.($value['icon'] ? 'fa fa-'.$value['icon']:'').'">'.$value['label'].'</a></li>';
		}
		$html .= '</ul>' . parent::toHTML();
		return $html;
	}
}

class GoogleMap extends Widget {
	public function __construct(){
		parent::__construct();
		$this->zoom = 5;
		$this->center = array(46.1072648, 3.4283353);
		$this->id = $this->name = 'map';
	}

	public function __set($key, $value) {
		$this->$key = $value;
		if( $key == 'apiKey' || $key == 'zoom' || $key == 'center' || $key == 'markers' ){
			$this->compileStyles();
		}
	}

	protected function compileStyles(){
		$this->styles['googleMap'] = stylesheet(array(), '.googlemap {
			width: 100%;
			height: 340px;
		}');
		$this->scripts['googleMap'] = javascript(array('src' => 'https://maps.googleapis.com/maps/api/js?key='.(isset($this->apiKey)?$this->apiKey:''))).javascript(array(), '
// Create an array of styles.
var styles = [{"featureType":"water","elementType":"geometry","stylers":[{"hue":"#165c64"},{"saturation":34},{"lightness":-69},{"visibility":"on"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"hue":"#b7caaa"},{"saturation":-14},{"lightness":-18},{"visibility":"on"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#cbdac1"},{"saturation":-6},{"lightness":-9},{"visibility":"on"}]},{"featureType":"road","elementType":"geometry","stylers":[{"hue":"#8d9b83"},{"saturation":-89},{"lightness":-12},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"hue":"#d4dad0"},{"saturation":-88},{"lightness":54},{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"hue":"#bdc5b6"},{"saturation":-89},{"lightness":-3},{"visibility":"simplified"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"hue":"#bdc5b6"},{"saturation":-89},{"lightness":-26},{"visibility":"on"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"hue":"#c17118"},{"saturation":61},{"lightness":-45},{"visibility":"on"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"hue":"#8ba975"},{"saturation":-46},{"lightness":-28},{"visibility":"on"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"hue":"#a43218"},{"saturation":74},{"lightness":-51},{"visibility":"simplified"}]},{"featureType":"administrative.province","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"simplified"}]},{"featureType":"administrative.neighborhood","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"geometry","stylers":[{"hue":"#cba923"},{"saturation":50},{"lightness":-46},{"visibility":"on"}]}];

  // Create a new StyledMapType object, passing it the array of styles,
  // as well as the name to be displayed on the map type control.
  var styledMap = new google.maps.StyledMapType(styles,
    {name: "'.(isset($this->name) ? $this->name : '').'"});

  // Create a map object, and include the MapTypeId to add
  // to the map type control.
  var mapOptions = {
    zoom: '.(isset($this->zoom) ? $this->zoom : '13').',
    '.(isset($this->center) ? 'center: new google.maps.LatLng('.$this->center[0].', '.$this->center[1].'),' : '').'
    mapTypeControlOptions: {
      mapTypeIds: [google.maps.MapTypeId.ROADMAP, "map_style"]
    }
  };
  var map = new google.maps.Map(document.getElementById("'.(isset($this->id) ? $this->id : '').'"),
    mapOptions);

  //Associate the styled map with the MapTypeId and set it to display.
  map.mapTypes.set("map_style", styledMap);
  map.setMapTypeId("map_style");

  // markers
	var msize = 0;
	var bounds = new google.maps.LatLngBounds();
	var markers = '.(isset($this->markers) ? json_encode($this->markers, true) : '').';
	for( var i = 0; i < markers.length; ++i ){
		var marker = markers[i];
		if( typeof(marker.image) != "undefined" && marker.image != ""){
		  	var img = {
		  		url: marker.image,
		  		scaledSize: new google.maps.Size(marker.width, marker.height),
		  		origin: new google.maps.Point(0, 0),
		  		anchor: new google.maps.Point(marker.width/2, marker.height/2),
		  	};
			var m = new google.maps.Marker({
				position: { lat: marker.lat, lng: marker.lng },
				map: map,
				icon: img,
				title: marker.title
			});
			if( typeof(marker.url) != "undefined" && marker.url != ""){
			  	m.addListener("click", function() {
					location.href = marker.url;
				});
			}
			bounds.extend(m.position);
		}
	}
	
	if( msize > 1 )
		map.fitBounds(bounds);
');
	}
	public function toHTML(){
		return '<div id="'.$this->id.'" class="googlemap"></div>';
	}
}


class DataList extends Widget {
	public $model = null;
	public $datas = array();
	public function __construct($model, $datas=null){
		parent::__construct();
		$this->model = $model;
		$this->class = 'datalist';
		$this->datas = !is_null($datas) ? $datas : $model->get();
		$this->attrs['showHead'] = $this->attrs['showFoot'] = true;
	}
	public function toHTML(){
		$labels = array();
		$body = array();
		$fields = $this->model->getFields();
		foreach ($fields as $key => $value) {
			$value->attributes['name'] = $key;
			$sql = $value->getSQLField();
			if( !isset($sql['relation']) || !$sql['relation']){
				$labels[] = $value->getFieldName();
			}
		}
		if( $this->datas ){
			foreach ($this->datas as $key => $value) {
				$b = array();
				foreach (array_keys($fields) as $f) {
					$field = $fields[$f];
					if( $f == 'id' ){
						$b[] = Model::generateField($field);
					}elseif( get_class($field) == 'DateTimeField' ){
						$b[] = time_elapsed_string($value[$f]);
					}elseif( isset($value[$f]) ){
						$b[] = $value[$f];
					}else{
						//$b[] = '';
					}
				}
				$body[] = $b;
			}
		}
		return tag('div', table(array(
			'caption' => isset($this->tableCaption) ? $this->tableCaption : ucfirst(sprintf(__('%s'), $this->model->getName())),
			'head' => $this->attrs['showHead'] ? $labels : null,
			'body' => $body,
			'foot' => $this->attrs['showFoot'] ? $labels : null,
		), array($this->class)), array('class' => $this->class));
	}
}

class RangeField extends NumberField {
	public function __construct($attributes){
		$attributes['class'] = 'rangefield';
		parent::__construct($attributes);
		$this->scripts['range-input'] = javascript(array('src' => 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/8.5.1/nouislider.min.js')).javascript(array(), '

');
		$this->styles['range-input'] = stylesheet(array('href' => 'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/8.5.1/nouislider.min.css'));
	}
	public function render(){
		return '<div class="rangefield"></div>';
	}
}