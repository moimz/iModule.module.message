<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지 박스를 가져온다.
 * 
 * @file /modules/message/process/getBox.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 21.
 */
if (defined('__IM__') == false) exit;

$box = Param('box');
$box = $this->getBox($box);
if ($box == null) {
	$results->success = false;
	return;
}

$results->success = true;
$results->box = $box;
?>