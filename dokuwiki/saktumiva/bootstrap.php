<?php

/**
 * Returns HTML string with script tag
 * @param  string $filename
 * @return string
 */
function tpl_js($filename)
{
	$filepath = DOKU_TPL . 'js' . DIRECTORY_SEPARATOR . $filename;
	return "<script type=\"application/javascript\" src=\"{$filepath}\"></script>";
}