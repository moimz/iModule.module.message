<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지 박스 목록를 가져온다.
 * 
 * @file /modules/message/process/getBoxes.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 5. 2.
 */
if (defined('__IM__') == false) exit;

if ($this->IM->getModule('member')->isLogged() == false) {
	$results->success = false;
	return;
}

$position = Request('position');
$mMember = $this->IM->getModule('member');
$lists = $this->db()->select($this->table->member.' m','m.box, m.midx, m.latest_message as latest, b.members')->join($this->table->box.' b','b.box=m.box','LEFT')->where('m.midx',$this->IM->getModule('member')->getLogged());
$total = $lists->copy()->count();
if ($position) $lists->where('m.latest_message',$position,'<=');
$lists = $lists->orderBy('m.latest_message','desc')->limit($position ? 10 : 15)->get();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$lists[$i] = $this->getBox($lists[$i]->box);
}

$first = $this->db()->select($this->table->member,'min(latest_message) as first')->where('midx',$this->IM->getModule('member')->getLogged())->getOne();
$first = isset($first->first) == true && $first->first ? $first->first : 0;

$results->success = true;
$results->lists = $lists;
$results->first = $first;
$results->total = $total;
?>