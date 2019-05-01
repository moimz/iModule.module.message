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
$hash = Param('hash');
$file = $this->db()->select($this->table->attachment)->where('hash',$hash)->getOne();
if ($file == null || $file->midx != $this->IM->getModule('member')->getLogged()) {
	$results->success = false;
	$results->message = $this->getErrorText('NOT_FOUND');
	return;
}

if (isset($_SERVER['HTTP_CONTENT_RANGE']) == true && preg_match('/bytes ([0-9]+)\-([0-9]+)\/([0-9]+)/',$_SERVER['HTTP_CONTENT_RANGE'],$fileRange) == true) {
	$chunkBytes = file_get_contents("php://input");;
	$chunkStart = intval($fileRange[1]);
	$chunkEnd = intval($fileRange[2]);
	$fileSize = intval($fileRange[3]);
	
	if ($fileSize != $file->size) {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_FILE_SIZE');
		return;
	}
	
	if ($chunkEnd - $chunkStart + 1 != strlen($chunkBytes)) {
		$results->success = false;
		$results->message = $this->getErrorText('INVALID_CHUNK_SIZE');
		return;
	}
	
	if ($chunkStart == 0) $fp = fopen($this->IM->getAttachmentPath().'/message/'.$file->path,'w');
	else $fp = fopen($this->IM->getAttachmentPath().'/message/'.$file->path,'a');
	
	fseek($fp,$chunkStart);
	fwrite($fp,$chunkBytes);
	fclose($fp);
	
	if ($chunkEnd + 1 === $fileSize) {
		if (intval($file->size) != filesize($this->IM->getAttachmentPath().'/message/'.$file->path)) {
			unlink($this->IM->getAttachmentPath().'/message/'.$file->path);
			$this->db()->delete($this->table->attachment)->where('hash',$file->hash)->execute();
			$results->success = false;
			$results->message = $this->getErrorText('INVALID_UPLOADED_SIZE');
		} else {
			$insert = array();
			$insert['mime'] = $mAttachment->getFileMime($this->IM->getAttachmentPath().'/message/'.$file->path);
			$insert['type'] = $mAttachment->getFileType($insert['mime']);
			$hash = md5_file($this->IM->getAttachmentPath().'/message/'.$file->path);
			$insert['path'] = $hash.'.'.base_convert(microtime(true)*10000,10,32).'.'.$mAttachment->getFileExtension($file->name,$this->IM->getAttachmentPath().'/message/'.$file->path);
			$insert['width'] = 0;
			$insert['height'] = 0;
			if ($insert['type'] == 'image') {
				$check = getimagesize($this->IM->getAttachmentPath().'/message/'.$file->path);
				$insert['width'] = $check[0];
				$insert['height'] = $check[1];
			}
			
			rename($this->IM->getAttachmentPath().'/message/'.$file->path,$this->IM->getAttachmentPath().'/message/'.$insert['path']);
			$this->db()->update($this->table->attachment,$insert)->where('hash',$file->hash)->execute();
			
			$results->success = true;
			$results->hash = $file->hash;
		}
	} else {
		$results->success = true;
		$results->uploaded = filesize($this->IM->getAttachmentPath().'/message/'.$file->path);
	}
} else {
	$results->success = false;
	$results->message = $this->getErrorText('INVALID_HTTP_CONTENT_RANGE');
}
?>