<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 기본템플릿 - 메시지박스
 * 
 * @file /modules/message/templets/default/box.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 21.
 */
if (defined('__IM__') == false) exit;

$IM->loadWebFont('XEIcon');
$IM->loadWebFont('Roboto');
?>
<div data-role="disabled"></div>
<aside>
	<h4>
		<?php echo $me->getText('context/box'); ?>
		
		<button type="button" data-action="setting"><i class="xi xi-cog"></i></button>
		<button type="button" data-action="new"><i class="mi mi-edit"></i></button>
		<button type="button" data-action="showbox"><i class="mi mi-close"></i></button>
	</h4>
	
	<div data-role="boxes">
		<div data-role="searchbox">
			<div data-role="input">
				<input type="search" placeholder="메시지 검색">
			</div>
			<button type="button"><i class="mi mi-search"></i></button>
		</div>
	</div>
</aside>

<section>
	<h5>
		<button type="button" data-action="showbox"><i class="mi mi-bars"></i></button>
		<button type="button" data-action="new"><i class="mi mi-edit"></i></button>
		
		<div data-role="title"></div>
		
		<div data-role="receiver">
			<div class="label">받는사람 : </div>
			<div data-role="input">
				<input type="text" placeholder="이름을 입력하세요.">
			</div>
		</div>
	</h5>
	
	<div data-role="posts">
		
	</div>
	
	<div data-role="inputbox">
		<div data-role="progress">
			<div></div>
		</div>
		
		<div data-role="file"></div>
		<div data-role="input">
			<textarea placeholder="메시지를 입력하세요."></textarea>
		</div>
		
		<div data-role="action">
			<button type="button" data-action="attachment"><i class="xi xi-clip"></i></button>
			<button type="button" data-action="send"><i class="xi xi-paper-plane"></i></button>
		</div>
	</div>
</section>