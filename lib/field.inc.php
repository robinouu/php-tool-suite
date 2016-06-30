<?php
/**
 * Fields are the way to communicate your validated data to data models.
 * 
 * They are very useful for immediate data validation like this email checker : 
 * ```php
 * $emailField = new EmailField(array('name' => 'emailField'));
 * $value = 'user@tld.com';
 * if ($emailField->validate($value)){
 * 	print 'valid email';
 * }else{
 *   print 'invalid email';
 * }
 * ```
 * 
 * As you can see, fields must be at least named individually (except when using models)
 * 
 * @package php-tool-suite
 * @subpackage Fields
 */

plugin_require(array('i18n', 'html'));

/**
 * Field abstract class
 * @subpackage Fields
 */
abstract class Field {

	static protected $instanceID = 0;

	public $attributes = array(
		'name' => 'fields[]',
	);

	/**
	 * Gets the field caption
	 * @return string the field name (or label if not empty)
 	 * @subpackage Fields
	 */
	public function getFieldName(){
		return isset($this->attributes['label']) ? $this->attributes['label'] : ucfirst($this->attributes['name']);
	}

	/**
	 * Validates a value using field specifications
	 * @param $value The value to test for
	 * @return boolean TRUE if the field have been validated, FALSE otherwise.
 	 * @subpackage Fields
	 */
	abstract public function validate(&$value);

	/**
	 * Returns an array of HTML attributes used by the field
	 * @return array An array of HTML attributes
 	 * @subpackage Fields
	 */
	abstract public function getHTMLAttributes();

	/**
	 * Returns an HTML representation of the field
	 * @return string The HTML tag
	 * @todo sublime text 3 form snippets
 	 * @subpackage Fields
	 */
	abstract public function getHTMLTag();

	/**
	 * Returns an SQL representation of the field
	 * @return array The SQL field properties
 	 * @subpackage Fields
	 */
	abstract public function getSQLField();

	abstract public function filter(&$value);
}

/**
 * Class TextField extends Field
 * 
 * A <textarea> field :
 * ```php
 * $message = new TextField(array('name' => 'message', 'maxlength' => -1));
 * ```
 * 
 * Attributes : 
 * value, id, name, readonly, required, placeholder, disabled, hidden, class
 * 
 * @subpackage Fields
 */
class TextField extends Field {
	
	public function __construct($attributes=array()) {
		$this->label = array('singular' => __('Texte'), 'plural' => __('Textes'));
		$this->attributes = array('id' => 'textField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function filter(&$value) {

	}
	
	public function getHTMLAttributes(){
		$attrs = array('type' => 'text');
		if( isset($this->attributes['value']) ){
			$attrs['value'] = $this->attributes['value'];
		}
		if( isset($this->attributes['id']) ){
			$attrs['id'] = $this->attributes['id'];
		}
		if( isset($this->attributes['name']) ){
			$attrs['name'] = $this->attributes['name'];
		}
		if( isset($this->attributes['class']) ){
			$attrs['class'] = $this->attributes['class'];
		}
		if( isset($this->attributes['value']) ){
			$attrs['value'] = $this->attributes['value'];
		}
		if( isset($this->attributes['readonly']) && $this->attributes['readonly'] === true ){
			$attrs['readonly'] = 'readonly';
			$attrs['aria-readonly'] = 'true';
		}
		if( isset($this->attributes['required']) && $this->attributes['required'] === true ){
			$attrs['aria-required'] = 'true';
		}
		if( isset($this->attributes['placeholder']) && is_string($this->attributes['placeholder']) ) {
			$attrs['placeholder'] = $this->attributes['placeholder'];
		}
		if( isset($this->attributes['disabled']) && $this->attributes['disabled'] === true ) {
			$attrs['disabled'] = $this->attributes['disabled'];
		}
		if( isset($this->attributes['hidden']) && $this->attributes['hidden'] === true ) {
			$attrs['type'] = 'hidden';
		}
		
		return $attrs;
	}

	public function getHTMLTag(){
		$attrs = $this->getHTMLAttributes();
		if( isset($this->attributes['maxlength']) && ($this->attributes['maxlength'] > 255 || $this->attributes['maxlength'] < 0) ){
			$value = '';
			if( isset($attrs['value']) ){
				$value = $attrs['value'];
				unset($attrs['value']);
			}
			$html = tag('textarea', $value, $attrs);
		}else{
			$html = tag('input', '', $attrs, true);
		}
		return $html;
	}

	public function validate(&$value){
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( !is_string($value) ){
			trigger('error', array('field' => $name, 'rule' => 'is_string'));
			return false;
			//$instance['errors']['invalid'] = t('le champ %s n\'est pas une chaîne de charactères', array($instance['label']));
		}else{
			if( isset($this->attributes['maxlength']) && strlen($value) > (int)$this->attributes['maxlength'] && (int)$this->attributes['maxlength'] >= 0 ){
				trigger('error', array('context' => $name, 'rule' => 'maxlength'));
				//$this->attributes['errors']['maxlength'] = t('le champ %s ne peut comporter plus de %d charactères', array($this->attributes['label'], $this->attributes['maxlength']));
			}elseif( isset($this->attributes['minlength']) && strlen($value) < (int)$this->attributes['minlength'] ){
				trigger('error', array('context' => $name, 'rule' => 'minlength'));
				//$this->attributes['errors']['minlength'] = t('le champ %s ne peut comporter moins de %d charactères', array($this->attributes['label'], $this->attributes['minlength']));
			}
			elseif( isset($this->attributes['required']) && trim($value) === '' ){
				trigger('error', array('context' => $name, 'rule' => 'required'));
				//$this->attributes['errors']['required'] = t('le champ %s est requis', array($this->attributes['label']));
			}else{
				return true;
			}
		}
		return false;
	}

	public function getSQLField() {
		$properties = array();
		$maxlength = isset($this->attributes['maxlength']) ? $this->attributes['maxlength'] : 255;
		if( $maxlength > 255 || $maxlength < 0 ){
			$properties['type'] = 'text';
		}else{
			$properties['type'] = 'varchar(' . (int)$maxlength . ')';
		}
		if( isset($this->attributes['required']) ){
			$properties['required'] = true;
		}
		if( isset($this->attributes['value']) ){
			$properties['default'] = $this->attributes['value'];
		}
		return $properties;
	}

}

class TextAreaField extends TextField {
	
	public function __construct($attributes=array()) {
		$this->labels = array('singular' => __('Texte sur plusieurs lignes'), 'plural' => __('Textes sur plusieur lignes'));
		$this->attributes = array('id' => 'numberField-'.(++Field::$instanceID), 'maxlength' => -1);
		$this->attributes = array_merge($this->attributes, $attributes);
	}
	
	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'textarea';
		$attrs['role'] = 'textbox';
		$attrs['aria-multiline'] = 'true';
		return $attrs;
	}
}


/**
 * class NumberField extends TextField
 * 
 * The number field is specific for integer and floating values.
 * 
 * ```php
 * $quantity = new NumberField(array('name' => 'quantity', 'min' => '5', 'max' => 99));
 * ```
 * 
 * Attributes : 
 * min, max, step
 * 
 * @subpackage Fields
 */
class NumberField extends TextField {
	
	public function __construct($attributes=array()) {
		$this->labels = array('singular' => __('Nombre entier'), 'plural' => __('Nombres entiers'));
		$this->attributes = array('id' => 'numberField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function validate(&$value){
		parent::getHTMLAttributes();
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( !is_int($value) ){
			trigger('error', array('context' => $name, 'rule' => 'is_int'));
			return false;
			//$instance['errors']['invalid'] = t('le champ %s n\'est pas un nombre entier', array($instance['label']));
		}else{
			if( isset($this->attributes['minValue']) && $value < (int)$this->attributes['minValue'] ){
				trigger('error', array('context' => $name, 'rule' => 'minValue'));
				return false;
				//$instance['errors']['minValue'] = t('le champ %s doit être supérieur à %d', array($instance['label'], $instance['value']));
			}elseif( isset($this->attributes['maxValue']) && $value > (int)$this->attributes['maxValue'] ){
				trigger('error', array('context' => $name, 'rule' => 'maxValue'));
				return false;
				//$instance['errors']['minValue'] = t('le champ %s doit être inférieur à %d', array($instance['label'], $instance['value']));
			}elseif( isset($this->attributes['required']) && trim($value) === '' ){
				trigger('error', array('context' => $name, 'rule' => 'required'));
				return false;
				//$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
			}
		}
		return true;
	}

	public function getHTMLTag(){
		$attrs = $this->getHTMLAttributes();
		if( isset($this->attributes['minValue']) ){
			$attrs['min'] = $this->attributes['minValue'];
		}
		if( isset($this->attributes['maxValue']) ){
			$attrs['max'] = $this->attributes['maxValue'];
		}
		if( isset($this->attributes['step']) ){
			$attrs['step'] = $this->attributes['step'];
		}
		return tag('input', '', $attrs, true);
	}


	public function getSQLField() {
		$properties = array();
		$min = isset($this->attributes['minValue']) ? $this->attributes['minValue'] : -PHP_INT_MAX;
		$max = isset($this->attributes['maxValue']) ? $this->attributes['maxValue'] : PHP_INT_MAX;
		$fl = isset($this->attributes['step']) && is_double($this->attributes['step']);
		if( $fl ){
			$properties['type'] = 'DOUBLE';
		}elseif( ($min >= -128 && $max <= 127) || ( $min >= 0 && $max <= 255 ) ){
			$properties['type'] = 'TINYINT';
		}elseif( ($min >= -32768 && $max <= 32767) || ( $min >= 0 && $max <= 65535 ) ){
			$properties['type'] = 'SMALLINT';
		}elseif( ($min >= -8388608 && $max <= 8388607) || ( $min >= 0 && $max <= 16777215 ) ){
			$properties['type'] = 'MEDIUMINT';
		}elseif( ($min >= -2147483648 && $max <= 2147483647) || ( $min >= 0 && $max <= 4294967295 ) ){
			$properties['type'] = 'INT';
		}elseif( ($min >= -9223372036854775808 && $max <= 9223372036854775807) || ( $min >= 0 && $max <= 18446744073709551615 ) ){
			$properties['type'] = 'BIGINT';
		}else{
			trigger('error', array('context' => 'numberfield_sql', 'rule' => 'integer'));
		}
		$properties['default'] = isset($this->attributes['value']) ? $this->attributes['value'] : 0;
		return $properties;
	}
}


/**
 * class BooleanField extends Field
 * 
 * A checkbox equivalent. Checks if its TRUE or FALSE.
 * 
 * ```php
 * $has_newsletter = new BooleanField(array('name' => 'newsletter'));
 * ```
 * 
 * Attributes : 
 * checked, value, id, name, readonly, required, placeholder, disabled, hidden, checcked
 * 
 * @subpackage Fields
 */
class BooleanField extends Field {

	public function __construct($attributes=array()) {
		$this->labels = array('singular' => __('Booléen'), 'plural' => __('Booléens'));
		$this->attributes = array('id' => 'booleanField-'.(++Field::$instanceID), 'label_position' => 'wrap');
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function filter(&$value) {

	}

	public function getHTMLAttributes(){
		$attrs = array('type' => 'text');
		if( isset($this->attributes['value']) ){
			$attrs['value'] = $this->attributes['value'];
		}
		if( isset($this->attributes['id']) ){
			$attrs['id'] = $this->attributes['id'];
		}
		if( isset($this->attributes['name']) ){
			$attrs['name'] = $this->attributes['name'];
		}
		if( isset($this->attributes['value']) ){
			$attrs['value'] = $this->attributes['value'];
		}
		if( isset($this->attributes['checked']) && $this->attributes['checked'] ){
			$attrs['checked'] = 'checked';
		}
		if( isset($this->attributes['readonly']) && $this->attributes['readonly'] === true ){
			$attrs['readonly'] = 'readonly';
			$attrs['aria-readonly'] = 'true';
		}
		if( isset($this->attributes['required']) && $this->attributes['required'] === true ){
			$attrs['aria-required'] = 'true';
		}
		if( isset($this->attributes['placeholder']) && is_string($this->attributes['placeholder']) ) {
			$attrs['placeholder'] = $this->attributes['placeholder'];
		}
		if( isset($this->attributes['disabled']) && $this->attributes['disabled'] === true ) {
			$attrs['disabled'] = $this->attributes['disabled'];
		}
		if( isset($this->attributes['hidden']) && $this->attributes['hidden'] === true ) {
			$attrs['type'] = 'hidden';
		}
		return $attrs;
	}
	public function getHTMLTag(){
		$attrs = $this->getHTMLAttributes();
		$attrs['type'] = 'checkbox';
		if( isset($instance['id']) ){
			$attrs['id'] = $instance['id'];
		}
		if( isset($instance['name']) ){
			$attrs['name'] = $instance['name'];
		}
		if( isset($instance['value']) ){
			$attrs['value'] = $instance['value'];
		}
		if( isset($instance['readonly']) && $instance['readonly'] === true ){
			$attrs['readonly'] = 'readonly';
			$attrs['aria-readonly'] = 'true';
		}
		if( isset($instance['required']) && $instance['required'] === true ){
			$attrs['aria-required'] = 'true';
		}
		if( isset($instance['disabled']) && $instance['disabled'] === true ) {
			$attrs['disabled'] = $instance['disabled'];
		}
		if( isset($instance['hidden']) && $instance['hidden'] === true ) {
			$attrs['type'] = 'hidden';
		}
		return tag('input', '', $attrs, true);
	}

	public function validate(&$value){
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( !is_bool($value) ){
			//$instance['errors']['invalid'] = t('le champ %s n\'est pas un booléen');
			trigger('error', array('context' => $name, 'rule' => 'is_bool'));
		}else{
			return true;
		}
		return false;
	}

	public function getSQLField() {
		$properties['type'] = 'BOOL';
		if( isset($this->attributes['value']) ){
			$properties['default'] = isset($this->attributes['value']) ? $this->attributes['value'] : 0;
		}
		return $properties;
	}
}


/**
 * class PasswordField extends TextField
 * 
 * A password field.
 * 
 * ```php
 * $password = new PasswordField(array('name' => 'password', 'required' => true, 'minlength' => 6));
 * ```
 * 
 * Validatation is inherited from textfield validator.
 * 
 * @subpackage Fields
 */
class PasswordField extends TextField {
	
	public function __construct($attributes=array()) {
		$this->labels = array('singular' => __('Mot de passe'), 'plural' => __('Mots de passe'));
		$this->attributes = array('id' => 'passwordField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'password';
		return $attrs;
	}
}

/**
 * class EmailField extends TextField
 * 
 * A textfield specific to email validation.
 * 
 * ```php
 * $email = new EmailField(array('name' => 'email'));
 * ```
 * 
 * Emails are validated according to RFCs 5321, 5322 and others.
 * 
 * @subpackage Fields
 */
class EmailField extends TextField {
	
	public function __construct($attributes=array()) {
		$this->labels = array('singular' => __('Email'), 'plural' => __('Emails'));
		$this->attributes = array('id' => 'passwordField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'email';
		return $attrs;
	}

	public function validate(&$value){
		plugin_require('vendor/is_email');
		$empty = trim($value) === '';
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( isset($this->attributes['required']) && $empty ){
			trigger('error', array('context' => $name, 'rule' => 'required'));
			return false;
			//$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
		}elseif( !$empty && !is_email($value) ){
			trigger('error', array('context' => $name, 'rule' => 'is_email'));
			return false;
			//$instance['errors']['invalid'] = t('le champ %s n\'est pas une adresse e-mail valide.', array($instance['label']));
		}
		return true;
	}
}


/**
 * class PhoneField extends TextField
 * 
 * A phone field.
 * 
 * ```php
 * $mobile = new PhoneField(array('name' => 'mobile'));
 * ```
 * 
 * It handles international validation
 * 
 * @subpackage Fields
 */
class PhoneField extends TextField {
	
	public function __construct($attributes) {
		$this->labels = array('singular' => __('Numéro de téléphone'), 'plural' => __('Numéros de téléphone'));
		$this->attributes = array('id' => 'phoneField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'tel';
		return $attrs;
	}

	public function validate(&$value){
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( isset($this->attributes['required']) && trim($value) === '' ){
			trigger('error', array('context' => $name, 'rule' => 'required'));
			//$instance['errors']['required'] = t('le champ %s est requis', array($instance['label']));
		}elseif( !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $value) ){
			trigger('error', array('context' => $name, 'rule' => 'is_phone_number'));
			//$instance['errors']['invalid'] = t('le champ %s n\'est pas un numéro de téléphone valide.', array($instance['label']));
		}else{
			return true;
		}
		return false;
	}
}

/**
 * class DateField extends TextField
 * 
 * A date field (handles international validation).
 * 
 * ```php
 * $mobile = new PhoneField(array('name' => 'mobile'));
 * ```
 * 
 * Attributes :
 * format, min, max
 * 
 * @subpackage Fields
 */
class DateField extends TextField {
	
	public function __construct($attributes) {
		$this->labels = array('singular' => __('Date'), 'plural' => __('Dates'));
		$this->attributes = array('id' => 'dateField-'.(++Field::$instanceID), 'formatWrite' => 'Y-m-d H:i:s');
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'date';
		if( isset($instance['min']) && is_string($instance['min']) ) {
			$attrs['min'] = $instance['min'];
		}
		if( isset($instance['max']) && is_string($instance['max']) ) {
			$attrs['max'] = $instance['max'];
		}
		return $attrs;
	}

	public function filter(&$value){
		$value = $this->dateFr($value);
	}

	public function validate(&$value){
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( isset($this->attributes['required']) && trim($value) === '' ){
			//$this->attributes['errors']['required'] = t('le champ %s est requis', array($this->attributes['label']));
			trigger('error', array('context' => $name, 'rule' => 'required'));
			return false;
		}else{
			$format = isset($this->attributes['format']) ? $this->attributes['format'] : __('Y-m-d H:i:s');
			$d = DateTime::createFromFormat($format, $value);
			$valid = $d && $d->format($format) == $value;

			if( !$valid ){
				trigger('error', array('context' => $name, 'rule' => 'is_date'));
				return false;
				//$this->attributes['errors']['invalid'] = t('le champ %s n\'est pas une date valide.', array($this->attributes['label']));
			}elseif( isset($this->attributes['min']) ){
				$dMin = DateTime::createFromFormat($format, $this->attributes['min']);
				if( $d < $dMin ){
					trigger('error', array('context' => $name, 'rule' => 'min'));
					return false;
					//$this->attributes['errors']['min'] = t('la date %s est plus ancienne que la date minimale acceptée (%s).', array($date, $this->attributes['min']));
				}
			}elseif( isset($this->attributes['max']) ){
				$dMax = DateTime::createFromFormat($format, $this->attributes['max']);
				if( $d > $dMax ){
					trigger('error', array('context' => $name, 'rule' => 'max'));
					return false;
					//$this->attributes['errors']['max'] = t('la date %s est plus récente que la date maximale acceptée (%s).', array($date, $this->attributes['max']));
				}
			}

			if( $d->format('d/m/Y H:i:s') == $value )
				$value = $d->format('Y-m-d H:i:s');
			else if( $d->format('d/m/Y') == $value )
				$value = $d->format('Y-m-d');
		}
		return true;
	}

	function dateFr($date){
		return strftime('%d/%m/%Y %H:%M:%S',strtotime($date));
	}

	public function frDate2($date, $format='Y-m-d H:i:s'){
		$d = explode(' ', $date);
		$days = explode('/', $d[0]);

		$enDate = $days[2] . '-' . $days[1] . '-' . $days[0] . ' ' . $d[1];
		$dt = new DateTime($enDate);

		return $dt->format($format);
	}

	public function getSQLField() {
		$properties['type'] = 'DATE';
		if( isset($this->attributes['value']) ){
			$properties['default'] = $this->attributes['value'];
		}
		return $properties;
	}
}

/**
 * class RelationField extends NumberField
 * 
 * A field specific to model relations
 * 
 * ```php
 * $fighter = new RelationField(array('name' => 'fighter', 'data' => 'player'));
 * ```
 * 
 * Relations are represented by INT(11) in sql databases.
 * 
 * @subpackage Fields
 */
class RelationField extends NumberField {
	public function getSQLField(){
		$properties['type'] = 'INT';
		$properties['relation'] = true;
		return $properties;
	}
}

/**
 * class DateTimeField extends DateField
 * 
 * A date/time field (Y-m-d H:i:s by default)
 * 
 * ```php
 * $created_at = new DateTimeField(array('name' => 'created_at'));
 * ```
 * 
 * Attributes : 
 * format
 * 
 * @subpackage Fields
 */
class DateTimeField extends DateField {
	
	public function __construct($attributes=array()) {
		$this->labels = array('singular' => t('Date et heure'), 'plural' => t('Dates et heures'));
		$this->attributes = array('id' => 'dateTimeField-'.(++Field::$instanceID), 'formatWrite' => 'Y-m-d H:i:s');
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'datetime';
		return $attrs;
	}

	public function validate(&$value){
		if( !isset($this->attributes['format']) ){
			$this->attributes['format'] = __('d/m/Y H:i:s');
		}
		return parent::validate($value);
	}

	public function getSQLField() {
		$properties['type'] = 'DATETIME';
		if( isset($this->attributes['value']) ){
			$properties['default'] = $this->attributes['value'];
		}
		return $properties;
	}
}


/**
 * class TimeField extends DateField
 * 
 * 
 * A time field (H:i:s by defult)
 * 
 * ```php
 * $hour = new TimeField(array('name' => 'hour'));
 * ```
 * 
 * Attributes : 
 * format
 * 
 * @subpackage Fields
 */
class TimeField extends DateField {
	
	public function __construct($attributes) {
		$this->labels = array('singular' => t('Heure'), 'plural' => t('Heures'));
		$this->attributes = array('id' => 'dateTimeField-'.(++Field::$instanceID), 'formatWrite' => 'H:i:s');
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function getHTMLAttributes(){
		$attrs = parent::getHTMLAttributes();
		$attrs['type'] = 'time';
		return $attrs;
	}

	public function validate(&$value){
		if( !isset($this->attributes['format']) ){
			$this->attributes['format'] = 'H:i:s';
		}
		return parent::validate($value);
	}

	public function getSQLField() {
		$properties['type'] = 'TIME';
		if( isset($this->attributes['value']) ){
			$properties['default'] = $this->attributes['value'];
		}
		return $properties;
	}
}


/**
 * class SelectField extends DateField
 * 
 * A field that contains a <select /> list of selection.
 * 
 * ```php
 * $lang = new SelectField(array('name' => 'lang', 'datas' => array('en', 'fr')));
 * ```
 * 
 * Attributes : 
 * datas : An associative array of key/value pairs or a simple array.
 * 
 * @subpackage Fields
 */
class SelectField extends Field {
	
	public function __construct($attributes) {
		$this->labels = array('singular' => t('Sélection'), 'plural' => t('Sélections'));
		$this->attributes = array('id' => 'selectField-'.(++Field::$instanceID));
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	public function filter(&$value){

	}

	public function validate(&$value){
		$name = isset($this->attributes['label']) ? $this->attributes['label'] : $this->attributes['name'];
		if( isset($this->attributes['required']) && trim($value) === '' ){
			//$instance['errors']['required'] = t('le champ %s est requis', array($this->attributes['label']));
			trigger('error', array('context' => $name, 'rule' => 'required'));
			return false;
		}elseif( !in_array($value, array_keys($this->attributes['datas'])) ){
			trigger('error', array('context' => $name, 'rule' => 'invalid'));
			return false;
			//$instance['errors']['invalid'] = t('la valeur %s ne fait pas parti de la liste de sélection.', array($select));
		}else{
			return true;
		}
	}
	public function getHTMLAttributes(){
		$attrs = array();//parent::getHTMLAttributes();
		if( isset($this->attributes['id']) ){
			$attrs['id'] = $this->attributes['id'];
		}
		if( isset($this->attributes['name']) ){
			$attrs['name'] = $this->attributes['name'];
		}
		if( isset($this->attributes['readonly']) && $this->attributes['readonly'] === true ){
			$attrs['readonly'] = 'readonly';
			$attrs['aria-readonly'] = 'true';
		}
		if( isset($this->attributes['required']) && $this->attributes['required'] === true ){
			$attrs['aria-required'] = 'true';
		}
		if( isset($this->attributes['disabled']) && $this->attributes['disabled'] === true ) {
			$attrs['disabled'] = $this->attributes['disabled'];
		}
		if( isset($this->attributes['hidden']) && $this->attributes['hidden'] === true ) {
			$attrs['type'] = 'hidden';
		}
		return $attrs;
	}

	public function getHTMLTag(){
		$attrs = $this->getHTMLAttributes();
		$content = '';
		if( $this->attributes['datas'] ){
			foreach ($this->attributes['datas'] as $key => $value) {
				$optAttrs = array('value' => $key);
				if( isset($this->attributes['value']) && $this->attributes['value'] == $key ){
					$optAttrs['selected'] = 'selected';
				}
				$content .= tag('option', $value, $optAttrs);
			}
		}
		return tag('select', $content, $attrs);
	}

	public function getSQLField(){
		$properties['type'] = 'VARCHAR(255)';
		if( isset($this->attributes['value']) ){
			$properties['default'] = $this->attributes['value'];
		}
		return $properties;
	}
}

