<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지를 전송한다.
 * 
 * @file /modules/message/process/send.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 5. 2.
 */
if (defined('__IM__') == false) exit;

$box = Param('box');
$file = Request('file');
$message = Request('message');
$sender = $this->IM->getModule('member')->getLogged();

if ($file == null && strlen($message) == 0) {
	$results->success = false;
	return;
}

if ($box == 'new') {
	$midxes = Param('midxes');
	if (is_array($midxes) == false || count($midxes) == 0) {
		$results->success = false;
		
		return;
	}
	
	$box = null;
	$midxes[] = $sender;
	$midxes = array_unique($midxes);
	sort($midxes);
	
	$hash = sha1(implode(',',$midxes));
	$exists = $this->db()->select($this->table->box)->where('hash',$hash)->get();
	foreach ($exists as $exist) {
		if ($exist->members == implode(',',$midxes)) {
			$box = $exist->box;
			break;
		}
	}
	
	if ($box == null) {
		$box = sha1($hash.time().rand(0,100000));
		$this->db()->setLockMethod('WRITE')->lock($this->table->box);
		while (true) {
			if ($this->db()->select($this->table->box)->where('box',$box)->has() == false) break;
			$box = sha1($hash.time().rand(0,100000));
		}
		
		$this->db()->insert($this->table->box,array('box'=>$box,'hash'=>$hash,'members'=>implode(',',$midxes)))->execute();
		$this->db()->unlock();
	}
} else {
	$midxes = $this->db()->select($this->table->box,'members')->where('box',$box)->getOne();
	if ($midxes == null) {
		$results->success = false;
		return;
	}
	$midxes = explode(',',$midxes->members);
}

$file = $this->db()->select($this->table->attachment)->where('hash',$file)->where('midx',$this->IM->getModule('member')->getLogged())->getOne();
if ($file != null) {
	$this->db()->update($this->table->attachment,array('box'=>$box,'status'=>'PUBLISHED'))->where('hash',$file->hash)->execute();
}

$this->db()->setLockMethod('WRITE')->lock(array($this->table->member,$this->table->post));
$reg_date = time() * 1000;
while (true) {
	if ($this->db()->select($this->table->post)->where('box',$box)->where('reg_date',$reg_date)->has() == false) break;
	$reg_date++;
}

foreach ($midxes as $midx) {
	if ($this->db()->select($this->table->member)->where('box',$box)->where('midx',$midx)->has() == false) {
		$this->db()->insert($this->table->member,array('box'=>$box,'midx'=>$midx,'first_message'=>$reg_date,'latest_message'=>$reg_date))->execute();
	}
	
	$post = array('box'=>$box,'midx'=>$midx,'reg_date'=>$reg_date,'sender'=>$sender,'message'=>$message);
	if ($file != null) $post['file'] = $file->hash;
	$this->db()->insert($this->table->post,$post)->execute();
}
$this->db()->unlock();
$this->db()->update($this->table->member,array('latest_message'=>$reg_date))->where('box',$box)->execute();
$this->db()->update($this->table->member,array('readed_message'=>$reg_date,'sended_message'=>$reg_date))->where('box',$box)->where('midx',$this->IM->getModule('member')->getLogged())->execute();

$results->success = true;
$results->box = $this->getBox($box);
?>