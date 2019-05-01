<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지를 불러온다.
 * 
 * @file /modules/message/process/getRecently.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 29.
 */
if (defined('__IM__') == false) exit;

if ($this->IM->getModule('member')->isLogged() == false) {
	$results->success = false;
	return;
}

$box = Request('box');
$boxes = $this->db()->select($this->table->member)->where('midx',$this->IM->getModule('member')->getLogged())->where('latest_message > readed_message')->get('box');
if ($box) $boxes[] = $box;
$boxes = array_unique($boxes);

for ($i=0, $loop=count($boxes);$i<$loop;$i++) {
	$boxes[$i] = $this->getBox($boxes[$i]);
}

$results->success = true;
$results->boxes = $boxes;
?>