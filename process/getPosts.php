<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지를 불러온다.
 * 
 * @file /modules/message/process/getPosts.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 27.
 */
if (defined('__IM__') == false) exit;

$box = Param('box');
$dir = Param('dir');
$position = Request('position');

$box = $this->getBox($box);
if ($box == null) {
	$results->success = false;
	return;
}

if ($dir == 'next' && !$position) {
	$unreads = $this->db()->select($this->table->post,'reg_date')->where('box',$box->box)->where('midx',$this->IM->getModule('member')->getLogged())->orderBy('reg_date','desc')->limit(20)->get('reg_date');
	if (in_array($box->midxes[$this->IM->getModule('member')->getLogged()]->readed,$unreads) == true) {
		$position = end($unreads) - 1;
	} else {
		$position = $box->midxes[$this->IM->getModule('member')->getLogged()]->readed;
	}
}

$lists = $this->db()->select($this->table->post)->where('box',$box->box)->where('midx',$this->IM->getModule('member')->getLogged())->limit(20);
if ($dir == 'prev') {
	$lists->where('reg_date',$position,'<')->orderBy('reg_date','desc');
}

if ($dir == 'next') {
	$lists->where('reg_date',$position,'>')->orderBy('reg_date','asc');
}
$lists = $lists->get();

$unreads = array();
for ($i=0, $loop=count($lists);$i<$loop;$i++) {
	$sender = $this->IM->getModule('member')->getMember($lists[$i]->sender);
	$lists[$i]->nickname = $sender->nickname;
	$lists[$i]->photo = $sender->photo;
	$lists[$i]->mode = $sender->idx == $this->IM->getModule('member')->getLogged() ? 'send' : 'receive';
	$lists[$i]->message = nl2br(GetString($lists[$i]->message,'replace'));
	if ($lists[$i]->file) {
		$file = $this->getFileInfo($lists[$i]->file);
		if ($file != null) $lists[$i]->file = $file;
	}
	
	if ($lists[$i]->is_readed == 'FALSE') $unreads[] = $lists[$i]->reg_date;
}

if (count($unreads) > 0) {
	$this->db()->update($this->table->post,array('is_readed'=>'TRUE'))->where('box',$box->box)->where('midx',$this->IM->getModule('member')->getLogged())->where('reg_date',$unreads,'IN')->execute();
}

if ($dir == 'next') {
	$latest = $this->db()->select($this->table->post,'max(reg_date) as latest')->where('box',$box->box)->where('midx',$this->IM->getModule('member')->getLogged())->where('is_readed','TRUE')->orderBy('reg_date','desc')->getOne();
	$this->db()->update($this->table->member,array('readed_message'=>$latest->latest))->where('box',$box->box)->where('midx',$this->IM->getModule('member')->getLogged())->execute();
}

$results->success = true;
$results->box = $this->getBox($box->box);
$results->position = $box->midxes[$this->IM->getModule('member')->getLogged()]->readed;
$results->lists = $lists;
?>