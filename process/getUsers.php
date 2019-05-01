<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지 수신자를 검색한다.
 * 
 * @file /modules/message/process/getUsers.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 21.
 */
if (defined('__IM__') == false) exit;

$mMember = $this->IM->getModule('member');
$keyword = Request('keyword');
$users = $mMember->db()->select($mMember->getTable('member'),'idx, nickname as display')->where('nickname','%'.$keyword.'%','LIKE')->orderBy('nickname','asc')->limit(10)->get();

$results->success = true;
$results->users = $users;
?>