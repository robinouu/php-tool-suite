<?php

if (version_compare(phpversion(), '4.2.0', '<') ) {
	mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
}