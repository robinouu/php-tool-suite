<?php

function email($options) {
	$options = array_merge(array(
		'to' => array(),
		'from' => '',
		'charset' => 'iso-8859-1',
		'content' => '',
		'html' => true
	), $options);

	$mime_boundary = md5(time());

	if( is_array($options['from']) ){
		$from = implode(', ', $options['from']);
	}else{
		$from = $options['from'];
	}

	$msg = 'From: ' . $from . "\n";
	$msg .= 'MIME-Version: 1.0'. "\n";
	$msg .= "Content-Type: multipart/mixed; boundary=\"".$mime_boundary."\"". "\n"; 
	 
	if( $options['html'] ){
		if( is_string($options['content']) ){
			$msg .= '--'.$mime_boundary. "\n";
			$msg .= 'Content-Type: text/html; charset=' . $options['charset'] . "\n";
			$msg .= 'Content-Transfer-Encoding: 7bit'. "\n\n";
			$msg .= $options['content'] . "\n\n";
		}elseif ( is_array($options['content']) ) {
			foreach ($options['content'] as $key => $value) {
				$msg .= '--'.$mime_boundary. "\n";
				$msg .= 'Content-Type: ' . $value['type'] . ';';
				if( $value['filename'] ){
					$msg .= 'name=\"'.$value['filename'].'\";'. "\n";
					$msg .= "Content-Transfer-Encoding: base64". "\n";
					$msg .= "Content-Disposition: attachment; filename=\"".$value['filename']."\"". "\n\n";
					$msg .= chunk_split(base64_encode($value['data'])) . "\n\n";
				}
			}
		}
	}else{
		if( is_string($options['content']) ){
			$msg .= '--'.$mime_boundary. "\n";
			$msg .= 'Content-Type: text/plain; charset=' . $options['charset'] . "\n";
			$msg .= $options['content'] . "\n\n";
		}
	}

	return mail($options['to'], $options['subject'], '', $msg);
}

email(array('from' => 'Robin Ruaux <robinouu@gmail.com>', 'to' => 'robinouu@gmail.com', 'subject' => 'test de contenu', 'content' => '<p><strong>Petit test</strong> en ta compagnie</p>'));
?>