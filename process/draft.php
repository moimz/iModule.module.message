<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일을 업로드한다.
 * 
 * @file /modules/message/process/draft.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 5. 2.
 */
if (defined('__IM__') == false) exit;

$mAttachment = $this->IM->getModule('attachment');
$name = Request('name');
$size = Request('size');
$type = Request('type') ? Request('type') : 'application/octet-stream';
$midx = $this->IM->getModule('member')->getLogged();

$this->db()->setLockMethod('WRITE')->lock($this->table->attachment);
$hash = sha1(time().rand(10000,99999));
while (true) {
	if ($this->db()->select($this->table->attachment)->where('hash',$hash)->has() == false) break;
	$hash = sha1(time().rand(10000,99999));
}

$mNormalizer = new UnicodeNormalizer();
$name = $mNormalizer->normalize($name);
$path = $this->getTempPath().'/'.$hash.'.temp';
$mime = $type;
$type = $mAttachment->getFileType($mime);
$size = $size;

$this->db()->insert($this->table->attachment,array('hash'=>$hash,'midx'=>$midx,'path'=>$path,'name'=>$name,'type'=>$type,'mime'=>$mime,'size'=>$size,'reg_date'=>time(),'exp_date'=>time() + 60 * 60 * 7))->execute();
$this->db()->unlock();

$results->success = true;
$results->hash = $hash;
$results->uploaded = 0;
$results->mime = $mime;
?>