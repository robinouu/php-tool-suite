<?php

require_once('sql.inc.php');

$scrudMaxRecursiveLevel = 32;

function scrud_get($dataName, $filter){
	$fields = array();
	if( isset($filter['fields']) && is_string($filter['fields']) ){
		$fields = array_map('trim', explode(',', $filter['fields']));
	}
	$query = sql_select(sql_quote($dataName), $fields);

	if( isset($filter['orderby']) && is_string($filter['orderby']) ){
		$query .= ' ORDER BY ' . $filter['orderby'];
	}
	if( isset($filter['limit']) ){
		$query .= ' LIMIT ' . (int)$filter['limit'];
	}
	//var_dump($query);
	return sql_query($query);
}

function scrud_search($dataName, $columns, $value, $order = array(), $start = 0, $length = null){
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$defaultFieldContent = var_get('sql/defaultFieldContent');

	$filter = array();
	$join = array();
	if( strlen(trim($value)) > 0 ) {
		foreach ($columns as $ck => $cv) {
			if( $cv['name'] === 'id'){
				$filter[] = $cv['name'] . ' LIKE ' . sql_quote('%' . $value . '%');
			}else{
				$field = array_merge($defaultSqlField, $database[$dataName]['fields'][$cv['name']]);
				if( $field['type'] === 'relation' ){
					// find a way to search in hasOne relation
					/*$tableAlias = lcfirst($field['data'][0]).uniqid();
					$join[] = ' INNER JOIN ' . sql_quote($field['data'], true) . $tableAlias .
						' ON ' . sql_quote('d.'.$cv['name'], true) . ' = ' . sql_quote($tableAlias.'.id', true);
					$filter[] = sql_quote($tableAlias.'.id', true) . ' LIKE ' . */
				}
				else if( $field['type'] === 'select' ){
					$potentials = preg_grep('/' . preg_quote($value) . '/ui', $field['data']);
					//$filterPotentials = array();
					foreach ($potentials as $kp => $v) {
						$s = array_search($v, $field['data']);
						$filter[] = $cv['name'] . ' LIKE ' . sql_quote('%' . $s . '%');
					}
				}else{
					$filter[] = $cv['name'] . ' LIKE ' . sql_quote('%' . $value . '%');
				}
			}
		}
	}

	$query = sql_select($dataName) . ' AS d';
	if( sizeof($join) ){
		$query .= implode(' ', $join);
	}
	$where = '';
	if( sizeof($filter) ){
		$where .= ' WHERE ' . implode(' OR ', $filter);
	}
	$query .= $where;

	if( is_array($order) && sizeof($order) ){
		$orders = array();
		foreach ($order as $key => $value) {
			$orders[] = $value['column'] . ' ' . $value['dir'];
		}
		$query .= ' ORDER BY ' . implode(',', $orders);
	}

	if( isset($_REQUEST['start']) && !is_null($start) ){
		$query .= ' LIMIT ' . (int)$start;
		if( isset($_REQUEST['length']) && !is_null($length) ) {
			$query .= ', ' . (int)$length;
		}
	}

	$res = sql_query($query);

	$resTotalLength = sql_query("SELECT COUNT(id) as nb FROM " . sql_quote($dataName, true) . $where);
	$recordsTotal = $resTotalLength[0]['nb'];

	$recordsFiltered = $res ? sizeof($res) : 0;

	$data = array();
	if( $res ){
		foreach ($res as $key => $value) {
			$fieldData = array();
			foreach ($value as $key => $value) {
				$c = $value;
				if( $key !== 'id' ){
					$field = $database[$dataName]['fields'][$key];
					$field = array_merge($defaultSqlField, $field);

					if( is_callable($field['formatter']) ){
						$c = $field['formatter']($value);
					}elseif( $field['type'] === 'relation' && isset($defaultFieldContent[$field['data']]) && is_callable($defaultFieldContent[$field['data']]) ){
						$q = sql_query(sql_select($field['data']) . ' WHERE id = ' . (int)($c));
						$c = $defaultFieldContent[$field['data']]($q[0]);
					}elseif( $field['type'] === 'select' ){
						$c = $field['data'][$value];
					}else{
						$c = $value;
					}
				}
				$fieldData[$key] = $c;
			}
			$data[] = $fieldData;
		}
	}
	return array('data' => $data, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsTotal);
}
function scrud_read($dataName) {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$defaultFieldContent = var_get('sql/defaultFieldContent');
	$prefix = var_get('sql/prefix');

	$data = $database[$dataName];
	$fields = $data['fields'];

	$tableName = $dataName;
	$table = sql_describe($config['db_prefix'] . $tableName);

	//$datas = sql_query(sql_select($tableName));
	$datas = array();

	$current_url = explode("?", $_SERVER['REQUEST_URI']);

	$html = '
<table>
	<thead>
		<tr>';
	foreach ($table as $field) {
		if( !isset($data['fields'][$field['Field']]) && $field['Field'] !== 'id'){
			continue;
		}
		$f = ($field['Field'] !== 'id') ? $data['fields'][$field['Field']] : false;
		if( isset($f['type']) && $f['type'] === 'relation' ){
			$c = isset($fields[$field['Field']]['label']) ? $fields[$field['Field']]['label'] : $database[$f['data']]['labels']['singular'];
		}else if ($field['Field'] !== 'id') {
			$c = isset($fields[$field['Field']]['label']) ? $fields[$field['Field']]['label'] : $field['Field'];
		}else{
			$c = '';
		}
		$html .= '
			<th>' . htmlspecialchars($c) . '</th>';
	}
	$html .= '
			
		</tr>
	</thead>
	<tbody>
	';
	if ($datas) {
		foreach ($datas as $d) {
			$html .= '<tr>
				';
			foreach ($table as $field) {
				if( $field['Field'] !== 'id' )
				{
					$f = array_merge($defaultSqlField, $fields[$field['Field']]);
					if( $f['type'] === 'relation' ){
						$ca = sql_query(sql_select($f['data']) . ' WHERE id=' . (int)$d[$field['Field']]);
						$ca = $ca[0];
						$html .= '<td>';
						if( $ca ) {
							if( isset($defaultFieldContent[$f['data']]) && is_callable($defaultFieldContent[$f['data']]) ){
								$html .= $defaultFieldContent[$f['data']]($ca);
							}else{
								$html .= '<ul>';
								foreach ($ca as $k => $c) {
									if( $c && $k != 'id'){
										$label = isset($database[$f['data']]['fields'][$k]['label']) ? $database[$f['data']]['fields'][$k]['label'] : $database[$f['data']]['labels']['singular'];
										$html .= '<li><strong>' . $label . '</strong> : ' . $c . '</li>';
									}
								}
								$html .= '</ul>';
							}
						}
						$html .= '</td>';
					}elseif( $f['type'] === 'select' ){
						$html .= '<td>' . htmlspecialchars($field['data'][$d[$field['Field']]]) . '&nbsp;</td>';
					}else{

						$html .= '<td>' . htmlspecialchars($d[$field['Field']]) . '&nbsp;</td>';
					}
				}
				else{
					$html .= '<td><input type="checkbox" name="ids[]" value="' . $d['id'] . '" />
						<a href="' . $current_url[0] . '?action=edit&amp;d=' . $dataName . '&amp;id=' . $d['id'] . '">&Eacute;diter</a> | 
						<a href="' . $current_url[0] . '?action=del&amp;d=' . $dataName . '&amp;id=' . $d['id'] . '">Supprimer</a>
					</td>';
				}
			}
			
			$html .= '
				
			</tr>';
		}
	}
	$html .='
	</tbody>
	';
	if (0 && !sizeof($datas)) {
		$html .= '
		<tfoot>
			<tr><td colspan="' . (int)(sizeof($table)) . '" style="text-align: center">Pas de données disponibles.</td></tr>
		</tfoot>';
	}
	$html .='
</table>';
	return $html;
}

function scrud_fields($dataName) {
	$schema = var_get('sql/schema/', array());
	return $schema[$dataName];
}
function scrud_field($dataName, $fieldName, $field, $prefix = '') {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	//$field = $database[$dataName]['fields'][$fieldName];
	$field = array_merge($defaultSqlField, $field);

	$html = '';
	$id = isset($field['id']) ? $field['id'] : 'input-'.$prefix.$fieldName;
	$fieldName = isset($field['name']) ? $field['name'] : $prefix.$fieldName;

	if( isset($field['value']) ){
		$value = $field['value'];
	}else{
		$value = isset($field['default']) ? $field['default'] : null;
	}

	switch ($field['type']) {
		case 'text':
		case 'hidden':
		case 'password':
		case 'date':
		case 'datetime':
		case 'search':
		case 'float':
		case 'double':
		case 'int':
			$isTextArea = false;
			if( $field['type'] === 'text' && isset($field['maxlength']) && $field['maxlength'] > 255){
				$id = !$field['id'] ? $field['id'] : 'textarea-' . $fieldName;
				$isTextArea = true;
				$html .= '<textarea name="' . $fieldName . '" id="' . $id . '" ';
			}else{
				$fieldType = $field['type'];
				if( $fieldType === 'float' || $fieldType === 'double' ){
					$html .= '<input type="number" step="any" name="' . $fieldName . '" id="' . $id . '" ';
				}elseif ($fieldType === 'int' ){
					$html .= '<input type="number" name="' . $fieldName . '" id="' . $id . '" ';
				}else{
					$html .= '<input type="' . $fieldType . '" name="' . $fieldName . '" id="' . $id . '" ';
				}
			}

			if( !in_array($field['type'], array('int','float','double')) ){
				$html .= (isset($field['maxlength']) && is_int($field['maxlength'])) ? 'maxlength="' . $field['maxlength'] . '" ' : '';
			}
			
			if( $field['type'] === 'datetime' ){
				$field['class'] = 'datetimepicker';
			}elseif( $field['type'] === 'date' ){
				$field['class'] = 'datepicker';
			}


			if( isset($field['placeholder']) && is_string($field['placeholder']) ) {
				$html .= 'placeholder="' . $field['placeholder'] . '" ';
			}else{
				$html .= 'placeholder="' . (isset($field['label']) ? $field['label'] : '') . '" ';
			}

			$html .= 'class="' . $field['class'] . '" ';

			if( !$isTextArea ){
				$html .= 'value="' . $value . '" ';
			}
			$html .= ' >';

			if( isset($isTextArea) && $isTextArea == true ){
				$html .= $value . '</textarea>';
			}
			break;
		case 'select':
		case 'relation':
		case 'enum':
			if( $field['type'] === 'relation' ){
				$data = scrud_list($field['data'], array());
				//$fieldName = $pkey;
			}else{
				$data = $field['data'];
			}
			if( is_array($data) ){
				$id = isset($field['id']) ? $field['id'] : 'select-' . $fieldName;
				$html .= '<select name="' . $fieldName . '" id="' . $id . '">';
				$assoc = is_assoc_array($data);
				foreach ($data as $key => $value) {
					$html .= '<option value="' . ($assoc ? $key : $value) . '">' . $value . '</option>';
				}
				$html .= '</select>';
			}else{
				$html .= 'Aucune donnée de type ' . $field['data'];
			}
		break;
		case 'phone':
		case 'email':
		case 'url':
			$type = array('phone' => 'tel', 'email' => 'email', 'url' => 'url');
			$html .= '<input type="' . (isset($type[$field['type']]) ? $type[$field['type']] : '') . '" name="' . $fieldName . '" id="' . $id . '" ';
			$html .= (isset($field['maxlength']) && is_int($field['maxlength'])) ? 'maxlength="' . $field['maxlength'] . '" ' : '';
			$html .= (isset($field['placeholder']) && is_string($field['placeholder'])) ? 'placeholder="' . $field['placeholder'] . '" ' : (isset($field['placeholder']) && !$field['placeholder']) ? '' : 'placeholder="' . $field['label'] . '" ';
			$html .= (isset($field['class']) && is_string($field['class'])) ? 'class="' . $field['class'] . '" ' : '';
			$html .= 'value="' . $value . '" ';
			$html .= '>';
			break;
		default:
			# code...
			break;
	}
	
	if( isset($field['label']) && $field['type'] !== 'hidden' ){
		$label = '<label for="' . $id . '">' . ucfirst($field['label']) . '</label>';
	}else{
		$label = '';
	}
	return $label . $html;
}

function scrud_relation_to_field($dataName, $relName) {
	$database = var_get('sql/schema');
	$data = $database[$dataName];
	$rel = isset($data['fields'][$relName]) ? $data['fields'][$relName] : null;
	if( $rel ){
		return $database[$rel['data']];
	}
	return null;
}

// STABLE
function scrud_edit_fields($dataName, $fields, $id, $populateWith = array(), $prefix = '') {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$defaultFieldContent = var_get('sql/defaultFieldContent');
	$prefix = var_get('sql/prefix');
	$html = '';
	foreach ($fields as $key => $field) {
		$field = array_merge($defaultSqlField, $field);
		$field['label'] = isset($field['label']) ? $field['label'] : $key;
		if (isset($field['value']) ){
			$value = $field['value'];
		}else{
			$value = isset($populateWith[$prefix . $key]) && $populateWith[$prefix . $key] ? $populateWith[$prefix . $key] : (isset($field['default']) ? $field['default'] : null);
		}
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		if( $field['type'] === 'relation' ){
			if( $field['data'] === $dataName ){
				$childFields = '';
			}else{
				$childFields = scrud_edit_fields($field['data'], $database[$field['data']]['fields'], $value, $populateWith, $pkey . '_');
			}
			$obj = null;
			if( $value ) {	
				$query = sql_query(sql_select($field['data']) . ' WHERE id = ' .(int)$value . ' LIMIT 1');
				$obj = $query[0];
			}

			$html .= '
			<fieldset>
				<legend>' . (isset($database[$field['data']]['fields'][$key]['label']) ? $database[$field['data']]['fields'][$key]['label'] : $database[$field['data']]['labels']['singular']) . '</legend>

				<a href="#" class="link-add-data">Créer</a>
				<div class="searchone">
					<label for="input-token-' . $pkey . '">Sélectionnez la donnée dans la liste</label>
					<input type="text" name="' . $pkey . '" id="input-token-' . $pkey . '" class="input-token" autocomplete="off" data-target="' . $field['data'] . '" value="' . (!is_null($value) ? $value : '') . '"';
			if( $obj ){
				$html .= ' data-id="' . $obj['id'] . '" data-title="' . (isset($defaultFieldContent[$field['data']]) && is_callable($defaultFieldContent[$field['data']]) ? $defaultFieldContent[$field['data']]($obj) : $obj['id']) . '"';
			}
			$html .= '/>
				</div>
				';
				if( $childFields != '' ){
			$html .= '
				<div class="newone">

					<input type="hidden" name="meta_newone_' . $pkey . '" value="' . (isset($populateWith['meta_newone_' . $pkey]) && (int)$populateWith['meta_newone_' . $pkey] === 1 ? 1 : 0) . '" />
					' . $childFields . '
				</div>';
				}
			$html .= '
				
			</fieldset>';
		}else{
			$html .= scrud_field($dataName, $key, $field, $prefix);
		}
	}
	return $html;	
}


function scrud_create_fields($dataName, $fields, $populateWith = array(), $prefix = '', $level = 0) {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$defaultFieldContent = var_get('sql/defaultFieldContent');
	$prefix = var_get('sql/prefix');

	$html = '';
	foreach ($fields as $key => $field) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$field = array_merge($defaultSqlField, $field);
		$field['label'] = isset($field['label']) ? $field['label'] : $key;
		if (isset($field['value']) ){
			$value = $field['value'];
		}else{
			$value = isset($populateWith[$prefix . $key]) && $populateWith[$prefix . $key] ? $populateWith[$prefix . $key] : (isset($field['default']) ? $field['default'] : null);
		}
		//$field['default'] = $value = isset($populateWith[$pkey]) ? $populateWith[$pkey] : null;
		if( $field['type'] === 'relation' ){
			if( $field['data'] === $dataName ){
				$childFields = '';
			}else{
				$childFields = scrud_create_fields($field['data'], $database[$field['data']]['fields'], $populateWith, $pkey . '_', $level + 1);
			}
			$obj = null;
			if( $value ) {
				$obj = sql_query(sql_select($field['data']) . ' WHERE id = ' .(int)$value . ' LIMIT 1');	
				$obj = $obj[0];
			}
			$html .= '
			<fieldset>
				<legend>' . (isset($database[$dataName]['fields'][$key]['label']) ? $database[$dataName]['fields'][$key]['label'] : $database[$field['data']]['labels']['singular']) . '</legend>

				<a href="#" class="link-add-data">Créer</a>
				<div class="searchone">
					<label for="input-token-' . $pkey . '">ou recherchez dans la base de données</label>
					<input type="text" name="' . $pkey . '" id="input-token-' . $pkey . '" class="input-token" autocomplete="off" data-target="' . $field['data'] . '" value="' . (!is_null($value) ? $value : '') . '"';
			if( $obj ){
				$html .= ' data-id="' . $obj['id'] . '" data-title="' . (isset($defaultFieldContent[$field['data']]) && is_callable($defaultFieldContent[$field['data']]) ? $defaultFieldContent[$field['data']]($obj) : $obj['id']) . '"';
			}
			$html .= '
					 />
				</div>
				
				';

				if( $childFields != '' ){
			$html .= '
				<div class="newone">
					<input type="hidden" name="meta_newone_' . $pkey . '" value="' . (isset($populateWith['meta_newone_' . $pkey]) && (int)$populateWith['meta_newone_' . $pkey] === 1 ? 1 : 0) . '" />
					' . $childFields . '
				</div>';
				}
			$html .= '
			</fieldset>';
		}else{
			$html .= scrud_field($dataName, $pkey, $field, '');
		}
	}
	return $html;
}

/* Cette fonction sauvegarde un groupe de données en base
 * Les données sont mappées par leur identifiant  :
 * description.title
 */
function scrud_register($dataName, &$datas, $prefix = ''){
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$data = $database[$dataName];
	$fields = $data['fields'];

	$d = array();
	foreach ($fields as $key => $f) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$f = array_merge($defaultSqlField, $f);
		if( $f['type'] === 'relation' ){
			if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1 ){
				$child_id = scrud_register($f['data'], $datas, $pkey . '_');
				//var_dump($child_id, 'meta_newone_'.$pkey);
				if( $child_id ){
					$datas['meta_newone_'.$pkey] = 0;
					$datas[$pkey] = $child_id;
					$d[$key] = $child_id;
				}
			}elseif( isset($datas[$pkey]) && is_numeric($datas[$pkey]) ){
				$d[$key] = (int)$datas[$pkey];
			}else{
				//var_dump($key, "BIT");
				$d[$key] = null;
			}

		}else{
			$d[$key] = isset($datas[$pkey]) ? $datas[$pkey] : null;
			/*if( isset($datas[$pkey]) && ( (bool)$f['required'] == true && trim($datas[$pkey]) != '') ){
				$d[$key] = $datas[$pkey];
			}else{
				$d[$key] = $datas[$pkey];
			}*/
			/*elseif( $f['unique'] === true && $f['required'] !== true ){
				var_dump($key, "3MAP");
				$d[$key] = null;
			}*/
		}
	}
	
	foreach ($d as $key => $value) {
		//var_dump(substr($key, 0, strlen('meta_')) === 'meta_', substr($key, 0, strlen('meta_')));	
		if( substr($key, 0, strlen('meta_')) === 'meta_' ){
			unset($d[$key]);
		}
	}

	if( sizeof($d) && sql_insert($dataName, $d) ){
		return sql_inserted_id();
	}else{
		return null;
	}
}

function scrud_update($dataName, $id, $datas, $prefix = ''){
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$data = $database[$dataName];
	$fields = $data['fields'];

	//var_dump($datas);
	$d = array();
	foreach ($fields as $key => $f) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$f = array_merge($defaultSqlField, $f);
		
		if( $f['type'] === 'relation' ){
			//var_dump($datas);
			if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1) {
				$child_datas = array();
				$child_id = null;
				//var_dump($datas);
				foreach ($datas as $k => $v){
					//var_dump($k, $pkey);
					if( substr($k, 0, strlen($pkey)) === $pkey ){
						$child_datas[$k] = $v;
					}elseif (substr($k, 0, strlen('meta_')) === 'meta_'){
						$child_datas[$k] = $v;
					}
				}
				//var_dump($child_datas);
				if( sizeof($child_datas) ){
					//var_dump($child_datas);
					$child_id = scrud_register($f['data'], $datas, $pkey . '_');

				}
				if( isset($child_id) && $child_id ){
					$d[$pkey] = $child_id;
					$datas['meta_newone_'.$pkey] = 0;
				}
			}else{
				if( isset($datas[$key]) && is_numeric($datas[$key]) ){
					$d[$pkey] = (int)$datas[$pkey];
				}
			}
		}else{
			if( isset($datas[$pkey]) ) {
				$d[$pkey] = $datas[$pkey];
			}
		}
	}

	//var_dump($d);
	return sql_update($dataName, $d, 'WHERE id = ' . $id);
}

function scrud_validate($dataName, $datas, $id = null, $prefix = '') {
	$defaultSqlField = var_get('sql/defaultField');
	$database = var_get('sql/schema');
	$prefix = var_get('sql/prefix');

	$data = $database[$dataName];

	$fields = $data['fields'];
	$errors = array();
	$back = array();
	
	foreach ($fields as $key => $field) {
		$pkey = trim($prefix) != '' ? $prefix . $key : $key;
		$errors[$pkey] = array();
		$field = array_merge($defaultSqlField, $field);
		
		if( isset($datas[$pkey]) ){
			$d = $datas[$pkey];
		}else{
			$d = isset($field['value']) ? $field['value'] : (isset($field['default']) ? $field['default'] : null);
		}
		/*if( $field['type'] !== 'relation' ){
			$d = isset($datas[$pkey]) ? $datas[$pkey] : (isset($field['default']) ? $field['default'] : '');
		}elseif( isset($datas[$pkey]) ){
			$d = isset($datas[$pkey]) && $datas[$pkey] ? $datas[$pkey] : null;
		}*/
		
		if( $field['type'] !== 'relation' || ($field['type'] === 'relation' && (
				!isset($datas['meta_newone_'.$pkey]) || (isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] !== 1)
				)) ) {

			if (($field['required'] && !isset($field['default']) && trim($d) == '')) {
				$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
			}else if( $field['required'] && (is_null($d) || $d == '') ){
				$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
			}
		}
		switch ($field['type']) {
			case 'phone':
				if( $field['required'] && !preg_match('#\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$#', $d)){
					$errors[$pkey][] = scrud_get_error_message($key, $field);
				}
			break;
			case 'text':
			case 'password':
			case 'email':
				if( $field['required'] && isset($field['maxlength']) && (int)$field['maxlength'] > 0 ){
					if( strlen($d) > (int)$field['maxlength'] ){
						$errors[$pkey][] = scrud_get_error_message($key, $field, 'maxlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter plus de ' . $field['maxlength'] . ' caractères';
					}
				}
				if( isset($field['minlength']) && (int)$field['minlength'] > 0 ){
					if( strlen($d) < (int)$field['minlength'] ){
						$errors[$pkey][] = scrud_get_error_message($key, $field, 'minlength'); //'Le champ "' . $field['label'] . '" ne peut pas comporter moins de ' . $field['minlength'] . ' caractères';
					}
				}
				if( $field['required'] && $field['type'] === 'email' ){
					if( !filter_var($d, FILTER_VALIDATE_EMAIL) ) {
						$errors[$pkey][] = scrud_get_error_message($key, $field, 'invalid_email'); //'L\'adresse email est invalide.';
					}
				}
					

				break;
			case 'relation':
				if( isset($datas['meta_newone_'.$pkey]) && (int)$datas['meta_newone_'.$pkey] === 1){
					$d = null;
					$child_datas = array();
					$child_id = null;

					$hasOne = false;
					$relData = $database[$field['data']]['fields'];

					$requiredChildren = $field['required'];
					foreach ($relData as $relKey => $relField) {
						$relField = array_merge($defaultSqlField, $relField);
						$value = isset($datas[$pkey . '_' . $relKey]) ? $datas[$pkey . '_' . $relKey] : null;
					
						if( sql_quote($value) !== 'NULL' ){
							$requiredChildren = false;
							$child_datas[$pkey . '_' . $relKey] = $value;
						}
					}
					foreach ($datas as $k => $v){
						if( substr($k, 0, strlen('meta_')) === 'meta_'){
							$child_datas[$k] = $v;
						}
					}

					if( $requiredChildren ){
						$errors[$pkey][] = scrud_get_error_message($key, $field, 'required');
					}
					else{
						//var_dump($child_datas, '<hr/>');
						$validation = scrud_validate($field['data'], $child_datas, $d, $pkey . '_');
						if( !$validation['valid'] ){
							$errors += $validation['errors'];
						}else{
							$back = array_merge_recursive($back, $validation['data']);
						}
					}
				}else if( isset($datas[$pkey]) ) {
					$back[$pkey] = $datas[$pkey];
				}
			break;
			default:
				# code...
				break;
		}
		//var_dump("ok");
		if( (bool)$field['unique'] === true && $d && (!isset($datas['meta_newone_'.$pkey]) || (int)$datas['meta_newone_'.$pkey] !== 1 ) ){//&& ($datas['meta_mode'] !== 'edit') ){

			$query = sql_select($dataName, 'id') . ' WHERE ' . sql_quote($key, true) . ' = ' . sql_quote($d);
			if( $id > 0 ){
				$query .= ' AND id <> ' . $id;
			}
			$query .= ' LIMIT 1';
			//var_dump($query);
			$exists = sql_query($query);
			if( $exists ){
				$errors[$pkey][] = scrud_get_error_message($key, $field, 'unique');
			}
		}

		if( !sizeof($errors[$pkey]) ){
			unset($errors[$pkey]);
		}

		if( $field['type'] === 'relation' ) {
			if( isset($validation) ){
				if( $validation['valid'] ){
					//var_dump($validation);
					$back = array_merge_recursive_unique($back, $validation['data']);
				}
			}elseif (isset($d)) {
				$back[$pkey] = $d;
			}
		}else{
			$back[$pkey] = $d ? $d : null;
		}
	}
//var_dump($back)
	foreach ($datas as $key => $value) {
		if( substr($key, 0, strlen('meta_')) === 'meta_'){
			$back[$key] = $value;
		}
	}

	//var_dump($back);

	if( sizeof($errors) ){
		return array('valid' => false, 'errors' => $errors);
	}

	return array('valid' => true, 'data' => $back);
}

function scrud_get_error_message($key, $field, $error = '') {
	global $database;
	$label = ucfirst(isset($field['label']) ? $field['label'] : ($field['type'] === 'relation' ? $database[$field['data']]['labels']['singular'] : $key));
	if( $error === 'required' ){
		return 'Champ "<strong>' . $label . '</strong>" requis';
	}elseif( $error === 'minlength' ){
		return 'Le champ "<strong>' . $label . '</strong>" ne peut pas comporter moins de ' . $field['minlength'] . ' caractères';
	}elseif( $error === 'maxlength' ){
		return 'Le champ "<strong>' . $label . '</strong>" ne peut pas comporter plus de ' . $field['maxlength'] . ' caractères';
	}elseif( $error === 'unique' ){
		return 'Le champ "<strong>' . $label . '</strong>" existe déjà en base de donnée.';
	}else{
		return 'Champ "<strong>' . $label . '</strong>" invalide.' . $error;
	}
}

function scrud_create($dataName, $validate = true) {
	global $database, $config;

	$data = $database[$dataName];

	if( $validate && isset($_REQUEST['send']) ){
		$validation = scrud_validate($dataName, $_REQUEST);
		if( $validation['valid'] ){
			// Save in database the object
			//var_dump($validation);
			$data_id = scrud_register($dataName, $validation['data']);
			if( $data_id ){
				
				print '
				<div class="msg msg-success">Enregistré (<strong>#' . $data_id . '</strong>) .</div>';	
			}else{
				print '
				<div class="msg msg-error">Enregistrement impossible.</div>';
			}
			
		}else{
			print '
			<div class="msg msg-error">Erreur à la validation : <br />';
			$errs = array();
			foreach ($validation['errors'] as $k => $value) {
			 	foreach( $value as $key => $err) {
			 		$errs[] = $err;
			 	}
			 }
			 print implode('<br />', array_values($errs)) . '</div>
			';
		}
	}

	$current_url = explode("?", $_SERVER['REQUEST_URI']);
	$html = '<form method="POST" action="' . $current_url[0] . '?action=create&d=' . $dataName . '" autocomplete="off">
		<input type="hidden" name="meta_mode" value="create" />';
	
	//var_dump($validation['data']);
	$html .= scrud_create_fields($dataName, $data['fields'], isset($validation['data']) ? $validation['data'] : $_REQUEST, '');
	$html .= '<input type="submit" name="send" value="Créer" /></form>';
	return $html;
}

function scrud_edit($dataName, $id = null, $fields = array(), $populateWith = null, $validate = true) {
	global $database, $config;

	$data = $database[$dataName];

	if( !$populateWith ){
		if( $id ){
			$populateWith = sql_query(sql_select($dataName) . ' WHERE id = ' . (int)$id);	
			if( $populateWith ){
				$populateWith = $populateWith[0];
			}else{
				$populateWith = $_REQUEST;
			}
		}else{
			$populateWith = $_REQUEST;
		}
	}
	if( $validate && isset($_REQUEST['send']) ){
		$validation = scrud_validate($dataName, $populateWith, $id);
		//var_dump($validation);
		if( $validation['valid'] ){
			// Save in database the object
			$result = scrud_update($dataName, $id, $validation['data']);
			if( $result ){
				print '
				<div class="msg msg-success">Modifié (<strong>#' . $id . '</strong>) .</div>';	
			}else{
				print '
				<div class="msg msg-error">Modification impossible.</div>';
			}
		}else{
			print '
			<div class="msg msg-error">Erreur à la validation : <br />';
			$errs = array();
			foreach ($validation['errors'] as $k => $value) {
			 	foreach( $value as $key => $err) {
			 		$errs[] = $err;
			 	}
			 }
			 print implode('<br />', array_values($errs)) . '</div>
			';
		}
	}

	$current_url = explode("?", $_SERVER['REQUEST_URI']);
	$html = '<form method="POST" action="' . $current_url[0] . '?action=edit&amp;d=' . $dataName . '&amp;id=' . $id . '">
		<input type="hidden" name="meta_mode" value="edit" />';
	
	$html .= scrud_edit_fields($dataName, $id, $data['fields'], $populateWith, '');
	$html .= '<input type="submit" name="send" value="Editer" /></form>';
	return $html;
}


function scrud_list($dataName, $filter, $printCallbacks = null) {
	sql_connect();

	$database = var_get('sql/schema');
	$printCallbacks = is_array($printCallbacks) ? $printCallbacks : var_get('sql/defaultFieldContent', array());

	$back = array();
	$fields = array();
	$query = '';
	$data = $database[$dataName];
	$res = array();
	if( isset($filter['search']) ){	
		$sql_fields = sql_describe($dataName);
		if( strlen(trim($search)) < 2 )
			return array();

		foreach ($sql_fields as $key => $value) {
			$fields[] = $value['Field'] . ' LIKE ' . sql_quote('%' . $search . '%'); 
		}
		//var_dump(sql_select($dataName) . ' WHERE ' . implode(' OR ', $fields));
		$res = sql_query(sql_select($dataName) . ' WHERE ' . implode(' OR ', $fields));
		if( !$res ) { return $back; }
	}
	else {
		if( isset($orderby['groupBy']) ){
			$query .= ' GROUP BY ';
			if( is_string($filter['groupBy']) ){
				$query .= $filter['groupBy'];
			}elseif( is_array($filter['groupBy']) ){
				$query .= implode(', ', $filter['groupBy']);
			}
		}
		if( isset($orderby['orderBy']) ){
			if( is_string($filter['orderBy']) ){
				$query .= ' ORDER BY ' . $orderby;
			}elseif( is_array($filter['groupBy']) ){
				$query .= implode(', ', $filter['orderBy']);
			}
		}
		if( isset($filter['limit']) && is_integer($filter['limit']) ){
			$query .= ' LIMIT ' . $filter['limit'];
		}
		$res = sql_query(sql_select($dataName) . $query);
	}
	if( $res ){
		foreach ($res as $key => $value) {
			$c = array();
			if( isset($printCallbacks[$dataName]) && is_callable($printCallbacks[$dataName]) ){
				$c = $printCallbacks[$dataName]($value);
			}elseif (isset($printCallbacks[$dataName]) && is_array($printCallbacks) ){
				foreach ($printCallbacks as $key => $v) {
					$c[] = $value[$key];
				}
			}else{
				foreach ($data['fields'] as $key => $v) {
					if( trim($value[$key]) != '' ){
						$c[] = $value[$key];
					}
				}
				$c = implode(', ', $c);
			}
			$back[$value['id']] = $c;
		}
	}
	//var_dump($back);
	return $back;
}

function scrud_remove($dataName, $id){
	$res = sql_query('DELETE FROM ' . sql_quote($dataName, true) . ' WHERE id = ' . (int)$id);
	return $res;
}