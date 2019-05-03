<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 회원간 전달할 수 있는 메시지와 관련된 전반적인 기능을 관리한다.
 * 
 * @file /modules/message/ModuleMessage.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 5. 2.
 */
class ModuleMessage {
	/**
	 * iModule 및 Module 코어클래스
	 */
	private $IM;
	private $Module;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private object $DB DB접속객체
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @private $settings 메시지환경설정정보
	 * @private $files 첨부파일정보
	 */
	private $settings = array();
	private $files = array();
	
	/**
	 * 기본 URL (다른 모듈에서 호출되었을 경우에 사용된다.)
	 */
	private $baseUrl = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @param Module $Module Module 코어클래스
	 * @see /classes/iModule.class.php
	 * @see /classes/Module.class.php
	 */
	function __construct($IM,$Module) {
		/**
		 * iModule 및 Module 코어 선언
		 */
		$this->IM = $IM;
		$this->Module = $Module;
		
		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see 모듈폴더의 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->box = 'message_box_table';
		$this->table->member = 'message_member_table';
		$this->table->post = 'message_post_table';
		$this->table->attachment = 'message_attachment_table';

		/**
		 * 알림서비스 수신하기 위한 자바스크립트를 로딩한다.
		 * 알림모듈은 글로벌모듈이기 때문에 모듈클래스 선언부에서 선언해주어야 사이트 레이아웃에 반영된다.
		 */
		if (defined('__IM_SITE__') == true || defined('__IM_ADMIN__') == true) {
			$this->IM->loadLanguage('module','message',$this->getModule()->getPackage()->language);
			$this->IM->addHeadResource('script',$this->getModule()->getDir().'/scripts/script.js');
		}
	}
	
	/**
	 * 모듈 코어 클래스를 반환한다.
	 * 현재 모듈의 각종 설정값이나 모듈의 package.json 설정값을 모듈 코어 클래스를 통해 확인할 수 있다.
	 *
	 * @return Module $Module
	 */
	function getModule() {
		return $this->Module;
	}
	
	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getModule()->getInstalled()->database);
		return $this->DB;
	}
	
	/**
	 * 모듈에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * URL 을 가져온다.
	 *
	 * @param string $view
	 * @param string $idx
	 * @return string $url
	 */
	function getUrl($view=null,$idx=null) {
		$url = $this->baseUrl ? $this->baseUrl : $this->IM->getUrl(null,null,false);
		
		$view = $view === null ? $this->getView($this->baseUrl) : $view;
		if ($view == null || $view == false) return $url;
		$url.= '/'.$view;
		
		$idx = $idx === null ? $this->getIdx($this->baseUrl) : $idx;
		if ($idx == null || $idx == false) return $url;
		
		return $url.'/'.$idx;
	}
	
	/**
	 * 다른모듈에서 호출된 경우 baseUrl 을 설정한다.
	 *
	 * @param string $url
	 * @return $this
	 */
	function setUrl($url) {
		$this->baseUrl = $this->IM->getUrl(null,null,$url,false);
		return $this;
	}
	
	/**
	 * view 값을 가져온다.
	 *
	 * @return string $view
	 */
	function getView() {
		return $this->IM->getView($this->baseUrl);
	}
	
	/**
	 * idx 값을 가져온다.
	 *
	 * @return string $idx
	 */
	function getIdx() {
		return $this->IM->getIdx($this->baseUrl);
	}
	
	/**
	 * [코어] 사이트 외부에서 현재 모듈의 API를 호출하였을 경우, API 요청을 처리하기 위한 함수로 API 실행결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 *
	 * @param string $protocol API 호출 프로토콜 (get, post, put, delete)
	 * @param string $api API명
	 * @param any $idx API 호출대상 고유값
	 * @param object $params API 호출시 전달된 파라메터
	 * @return object $datas API처리후 반환 데이터 (해당 데이터는 /api/index.php 를 통해 API호출자에게 전달된다.)
	 * @see /api/index.php
	 */
	function getApi($protocol,$api,$idx=null,$params=null) {
		$data = new stdClass();
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeGetApi',$this->getModule()->getName(),$api,$values);
		
		/**
		 * 모듈의 api 폴더에 $api 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/api/'.$api.'.'.$protocol.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterGetApi',$this->getModule()->getName(),$api,$values,$data);
		
		return $data;
	}
	
	/**
	 * [사이트관리자] 모듈 설정패널을 구성한다.
	 *
	 * @return string $panel 설정패널 HTML
	 */
	function getConfigPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this->getModule();
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/configs.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * [사이트관리자] 모듈 관리자패널 구성한다.
	 *
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel() {
		/**
		 * 설정패널 PHP에서 iModule 코어클래스와 모듈코어클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		$Module = $this;
		
		ob_start();
		INCLUDE $this->getModule()->getPath().'/admin/index.php';
		$panel = ob_get_contents();
		ob_end_clean();
		
		return $panel;
	}
	
	/**
	 * [사이트관리자] 모듈의 전체 컨텍스트 목록을 반환한다.
	 *
	 * @return object $lists 전체 컨텍스트 목록
	 */
	function getContexts() {
		$lists = array();
		foreach ($this->getText('context') as $context=>$title) {
			$lists[] = array('context'=>$context,'title'=>$title);
		}
		
		return $lists;
	}
	
	/**
	 * 특정 컨텍스트에 대한 제목을 반환한다.
	 *
	 * @param string $context 컨텍스트명
	 * @return string $title 컨텍스트 제목
	 */
	function getContextTitle($context) {
		return $this->getText('context/'.$context);
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 환경설정을 구성한다.
	 *
	 * @param object $site 설정대상 사이트
	 * @param string $context 설정대상 컨텍스트명
	 * @return object[] $configs 환경설정
	 */
	function getContextConfigs($site,$context) {
		$configs = array();
		
		$templet = new stdClass();
		$templet->title = $this->IM->getText('text/templet');
		$templet->name = 'templet';
		$templet->type = 'templet';
		$templet->use_default = true;
		$templet->value = $values != null && isset($values->templet) == true ? $values->templet : '#';
		$configs[] = $templet;
		
		return $configs;
	}
	
	/**
	 * 사이트맵에 나타날 뱃지데이터를 생성한다.
	 *
	 * @param string $context 컨텍스트종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return object $badge 뱃지데이터 ($badge->count : 뱃지숫자, $badge->latest : 뱃지업데이트 시각(UNIXTIME), $badge->text : 뱃지텍스트)
	 * @todo check count information
	 */
	function getContextBadge($context,$config) {
		/**
		 * null 일 경우 뱃지를 표시하지 않는다.
		 */
		return null;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메시지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		if ($this->lang == null) {
			if (is_file($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getModule()->getPackage()->language && is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getModule()->getPath().'/languages/'.$this->getModule()->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		$this->IM->fireEvent('afterGetText',$this->getModule()->getName(),$code,$returnString);
		
		/**
		 * 언어셋 텍스트가 없는경우 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메시지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		$error->type = 'BACK';
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 템플릿 정보를 가져온다.
	 *
	 * @param string $this->getTemplet($configs) 템플릿명
	 * @return string $package 템플릿 정보
	 */
	function getTemplet($templet=null) {
		$templet = $templet == null ? '#' : $templet;
		
		/**
		 * 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정일 경우
		 */
		if (is_object($templet) == true) {
			$templet_configs = $templet !== null && isset($templet->templet_configs) == true ? $templet->templet_configs : null;
			$templet = $templet !== null && isset($templet->templet) == true ? $templet->templet : '#';
		} else {
			$templet_configs = null;
		}
		
		/**
		 * 템플릿명이 # 이면 모듈 기본설정에 설정된 템플릿을 사용한다.
		 */
		if ($templet == '#') {
			$templet = $this->getModule()->getConfig('templet');
			$templet_configs = $this->getModule()->getConfig('templet_configs');
		}
		
		return $this->getModule()->getTemplet($templet,$templet_configs);
	}
	
	/**
	 * 모듈 외부컨테이너를 가져온다.
	 *
	 * @param string $container 컨테이너명
	 * @return string $html 컨텍스트 HTML
	 */
	function getContainer($container) {
		if ($container == 'thumbnail') {
			$hash = $this->getView();
			$file = $this->db()->select($this->table->attachment)->where('hash',$hash)->getOne();

			if ($file == null) {
				header("HTTP/1.1 404 Not Found");
				exit;
			} else {
				if (file_exists($this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb') == true) {
					if ($file->type == 'image') header('Content-Type: '.$file->mime);
					else header('Content-Type: image/jpeg');
					header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb'));
					readfile($this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb');
					exit;
				} elseif ($file->type == 'image' && file_exists($this->IM->getAttachmentPath().'/message/'.$file->path) == true) {
					if ($this->IM->getModule('attachment')->createThumbnail($this->IM->getAttachmentPath().'/message/'.$file->path,$this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb',($file->width <= $file->height ? 300 : 0),($file->width > $file->height ? 300 : 0),false) == false) {
						header("HTTP/1.1 404 Not Found");
						exit;
					}
					header('Content-Type: '.$file->mime);
					header('Content-Length: '.filesize($this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb'));
					readfile($this->IM->getAttachmentPath().'/message/'.$file->path.'.thumb');
					exit;
				} else {
					header("HTTP/1.1 404 Not Found");
					exit;
				}
			}
			exit;
		}
		
		$html = $this->getContext($container);
		
		$this->IM->addHeadResource('style',$this->getModule()->getDir().'/styles/container.css');
		
		$this->IM->removeTemplet();
		$footer = $this->IM->getFooter();
		$header = $this->IM->getHeader();
		
		return $header.$html.$footer;
	}
	
	/**
	 * 페이지 컨텍스트를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext($context,$configs=null) {
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html = PHP_EOL.'<!-- MESSAGE MODULE -->'.PHP_EOL.'<div data-role="context" data-type="module" data-module="'.$this->getModule()->getName().'" data-base-url="'.($this->baseUrl == null ? $this->IM->getUrl(null,null,false) : $this->baseUrl).'" data-context="'.$context.'" data-configs="'.GetString(json_encode($configs),'input').'">'.PHP_EOL;
		
		/**
		 * 컨텍스트 헤더
		 */
		$html.= $this->getHeader($context,$configs);
		
		/**
		 * 컨테이너 종류에 따라 컨텍스트를 가져온다.
		 */
		switch ($context) {
			case 'box' :
				$html.= $this->getBoxContext($configs);
				break;
				
			case 'setting' :
				$html.= $this->getSettingContext($configs);
				break;
		}
		
		/**
		 * 컨텍스트 푸터
		 */
		$html.= $this->getFooter($context,$configs);
		
		/**
		 * 컨텍스트 컨테이너를 설정한다.
		 */
		$html.= PHP_EOL.'</div>'.PHP_EOL.'<!--// MESSAGE MODULE -->'.PHP_EOL;
		
		return $html;
	}
	
	/**
	 * 컨텍스트 헤더를 가져온다.
	 *
	 * @param string $qid 문의게시판 ID
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getHeader($context,$configs=null) {
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getHeader(get_defined_vars());
	}
	
	/**
	 * 컨텍스트 푸터를 가져온다.
	 *
	 * @param string $context 컨테이너 종류
	 * @param object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	function getFooter($context,$configs=null) {
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getFooter(get_defined_vars());
	}
	
	/**
	 * 에러메시지를 반환한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @return $html 에러메시지 HTML
	 */
	function getError($code,$value=null) {
		/**
		 * iModule 코어를 통해 에러메시지를 구성한다.
		 */
		$error = $this->getErrorText($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 메시지박스 컨텍스트를 가져온다.
	 *
	 * @param object $confgs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return $html 컨텍스트 HTML
	 */
	function getBoxContext($configs=null) {
		if ($this->IM->getModule('member')->isLogged() == false) return $this->getError('REQUIRED_LOGIN');
		
		$header = PHP_EOL.'<form id="ModuleMessageBoxForm">'.PHP_EOL;
		$header.= '<input type="file">'.PHP_EOL;
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Message.init("ModuleMessageBoxForm");</script>'.PHP_EOL;
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		return $this->getTemplet($configs)->getContext('box',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 수신함을 정보를 가져온다.
	 *
	 * @param string $box 메시지박스고유값
	 * @param int $midx 회원고유값
	 * @return object $box
	 */
	function getBox($box,$midx=null) {
		$midx = $midx == null ? $this->IM->getModule('member')->getLogged() : $midx;
		
		$box = $this->db()->select($this->table->member.' m','m.box, m.midx, m.first_message as first, m.readed_message as readed, m.latest_message as latest, b.members')->join($this->table->box.' b','b.box=m.box','LEFT')->where('m.box',$box)->where('m.midx',$midx)->getOne();
		if ($box == null) return null;
		
		$box->readed = intval($box->readed);
		$box->latest = intval($box->latest);
		
		$mMember = $this->IM->getModule('member');
		$members = explode(',',$box->members);
		$members = $this->db()->select($this->table->member.' b','m.idx, m.nickname, b.sended_message as sended, b.readed_message as readed')->join($mMember->getTable('member').' m','m.idx=b.midx','LEFT')->where('b.box',$box->box)->orderBy('b.sended_message','desc')->orderBy('m.nickname','asc')->get();
		$box->midxes = array();
		$box->members = array();
		$box->readeds = array();
		foreach ($members as $member) {
			$box->midxes[$member->idx] = $member;
			if ($member->idx != $midx) $box->members[] = $member;
			$box->readeds[] = $member->readed;
		}
		sort($box->readeds);
		$box->member_hash = sha1(json_encode($box->members));
		
		$latest = $this->db()->select($this->table->post)->where('box',$box->box)->where('midx',$midx)->where('reg_date',$box->latest)->getOne();
		if ($latest != null) {
			$box->message = GetString($latest->message,'replace');
			if ($latest->file) {
				$file = $this->getFileInfo($latest->file);
				if ($file != null) $box->file = $file;
				else $box->file = $latest->file;
			} else {
				$box->file = null;
			}
		} else {
			$box->message = null;
		}
		
		$box->unread = $box->readed == $box->latest ? 0 : $this->db()->select($this->table->post)->where('box',$box->box)->where('midx',$midx)->where('reg_date',$box->readed,'>')->count();
		
		return $box;
	}
	
	/**
	 * 알림갯수를 가져온다.
	 *
	 * @param string $type 가져올형식 (ALL : 전체, UNCHECKED : 확인하지 않은 알림, UNREADED : 읽지 않은 알림)
	 * @return int $count
	 */
	function getPushCount($type='ALL') {
		if ($this->IM->getModule('member')->isLogged() == false) return 0;
		
		$check = $this->db()->select($this->table->push)->where('midx',$this->IM->getModule('member')->getLogged());
		if ($type == 'UNCHECKED') $check->where('is_checked','FALSE');
		elseif ($type == 'UNREADED') $check->where('is_readed','FALSE');
		
		return $check->count();
	}
	
	/**
	 * 알림메시지를 가져온다.
	 *
	 * @param string $module 알림을 보낸 모듈명
	 * @param string $code 알림코드
	 * @param string $content 알림데이터
	 * @return object $message
	 */
	function getPushMessage($module,$code,$contents) {
		$mModule = $this->IM->getModule($module);
		
		$message = null;
		if (method_exists($mModule,'syncPush') == true) {
			$push = new stdClass();
			$push->code = $code;
			$push->contents = json_decode($contents);
			$message = $mModule->syncPush('message',$push);
		}
		
		if ($message == null) {
			$message = new stdClass();
			$message->message = '['.$module.'] '.$contents;
			$message->icon = $this->getModule()->getDir().'/images/unknown.png';
		}
		
		return $message;
	}
	
	/**
	 * 첨부파일 임시폴더 경로를 가져온다.
	 *
	 * @param boolean $isFullPath 전체경로포함여부
	 * @return string $path
	 */
	function getTempPath($isFullPath=false) {
		$folder = 'temp';
		if (is_dir($this->IM->getAttachmentPath().'/message/'.$folder) == false) {
			mkdir($this->IM->getAttachmentPath().'/message/'.$folder);
			chmod($this->IM->getAttachmentPath().'/message/'.$folder,0707);
		}
		
		if ($isFullPath == true) $folder = $this->IM->getAttachmentPath().'/message/'.$folder;
		return $folder;
	}
	
	/**
	 * 첨부파일 정보를 가져온다.
	 *
	 * @param string $hash 파일고유값
	 * @return object $file 파일정보
	 */
	function getFileInfo($hash) {
		if (isset($this->files[$hash]) == true) return $this->files[$hash];
		
		$file = $this->db()->select($this->table->attachment)->where('hash',$hash)->getOne();
		if ($file == null) {
			$this->files[$hash] = null;
			return null;
		}
		
		$file->icon = $this->IM->getModule('attachment')->getFileIcon($file->type,$this->IM->getModule('attachment')->getFileExtension($file->name));
		$file->path = $this->IM->getModuleUrl('message','download',$hash,$file->name);
		
		if ($file->type == 'image') {
			$file->thumbnail = $this->IM->getModuleUrl('message','thumbnail',$hash,$file->name);
		}
		
		$this->files[$hash] = $file;
		return $this->files[$hash];
	}
	
	/**
	 * 현재 모듈에서 처리해야하는 요청이 들어왔을 경우 처리하여 결과를 반환한다.
	 * 소스코드 관리를 편하게 하기 위해 각 요쳥별로 별도의 PHP 파일로 관리한다.
	 * 작업코드가 '@' 로 시작할 경우 사이트관리자를 위한 작업으로 최고관리자 권한이 필요하다.
	 *
	 * @param string $action 작업코드
	 * @return object $results 수행결과
	 * @see /process/index.php
	 */
	function doProcess($action) {
		$results = new stdClass();
		
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('beforeDoProcess',$this->getModule()->getName(),$action,$values);
		
		/**
		 * 모듈의 process 폴더에 $action 에 해당하는 파일이 있을 경우 불러온다.
		 */
		if (is_file($this->getModule()->getPath().'/process/'.$action.'.php') == true) {
			INCLUDE $this->getModule()->getPath().'/process/'.$action.'.php';
		}
		
		unset($values);
		$values = (object)get_defined_vars();
		$this->IM->fireEvent('afterDoProcess',$this->getModule()->getName(),$action,$values,$results);
		
		return $results;
	}
}
?>