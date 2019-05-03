/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지모듈 화면 UI 이벤트를 처리한다.
 * 
 * @file /modules/message/scripts/script.js
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 21.
 */
var Message = {
	$form:null,
	boxes:{},
	$boxes:null,
	$posts:null,
	$searchbox:null,
	$inputbox:null,
	$progress:null,
	$title:null,
	$recevier:null,
	latest:null,
	timer:null,
	getUrl:function(view,idx) {
		var url = $("div[data-module=message]").attr("data-base-url") ? $("div[data-module=message]").attr("data-base-url") : ENV.getUrl(null,null,false);
		if (!view || view == false) return url;
		url+= "/"+view;
		if (!idx || idx == false) return url;
		return url+"/"+idx;
	},
	init:function(id) {
		var $form = $("#"+id);
		Message.$form = $form;
		
		if (id == "ModuleMessageBoxForm") {
			Message.$boxes = $("div[data-role=boxes]",$form);
			Message.box.init();
			
			Message.$receiver = $("div[data-role=receiver]",$form);
			Message.receiver.init();
			
			Message.$inputbox = $("div[data-role=inputbox]",$form);
			Message.inputbox.init();
			
			Message.$progress = $("div[data-role=progress]",Message.$inputbox);
			
			Message.$searchbox = $("div[data-role=searchbox]",$form);
			
			Message.$posts = $("div[data-role=posts]",$form);
			
			Message.$title = $("div[data-role=title]",$form);
			
			$("button[data-action]",$form).on("click",function() {
				var $button = $(this);
				var action = $button.attr("data-action");
				
				if (action == "new") {
					Message.box.view("new");
				}
				
				if (action == "showbox") {
					$("aside",$form).toggleClass("opened");
					if ($("aside",$form).hasClass("opened") == true) $("div[data-role=disabled]",$form).show();
					else $("div[data-role=disabled]",$form).hide();
				}
				
				if (action == "attachment") {
					$("input[type=file]",$form).trigger("click");
				}
				
				if (action == "send") {
					Message.inputbox.send();
				}
			});
			
			$("div[data-role=disabled]",$form).on("click",function() {
				$("aside",$form).removeClass("opened");
				$(this).hide();
			});
			
			Message.inputbox.disable();
			
			$(window).on("popstate",function(e) {
				var state = location.href.split(Message.getUrl(false)).pop().split("?").shift().split("/");
				var box = state[1];
				Message.box.view(box);
			});
			
			setTimeout(Message.reload,30000);
		}
	},
	/**
	 * 타이틀
	 */
	title:{
		init:function() {
			
		},
		print:function(box) {
			if (box === "new") {
				Message.$title.hide();
				Message.$receiver.show();
			} else {
				var data = Message.box.getBox(box);
				if (data === null) return;
				
				var members = [];
				for (var i=0, loop=data.members.length;i<loop;i++) {
					members.push(data.members[i].nickname);
				}
				
				Message.$title.html(members.join(", "));
				
				Message.$title.show();
				Message.$receiver.hide();
			}
		}
	},
	/**
	 * 메시지박스
	 */
	box:{
		init:function() {
			Message.$boxes.data("loading",false).data("first",0).data("scroll",0).data("total",-1);
			
			Message.$boxes.on("scroll",function() {
				if ($(this).data("scroll") < $(this).scrollTop() && $(this).scrollTop() + $(this).height() > $(this).prop("scrollHeight") - 100) {
					Message.box.getBoxes();
				}
				$(this).data("scroll",$(this).scrollTop());
			});
			
			Message.box.getBoxes(function() {
				var state = location.href.split(Message.getUrl(false)).pop().split("?").shift().split("/");
				if (state.length > 1) {
					var box = state[1];
					Message.box.view(box);
				}
			});
		},
		getBox:function(box,callback) {
			if (Message.boxes[box] !== undefined) {
				if (typeof callback == "function") callback(Message.boxes[box]);
				else return Message.boxes[box];
			} else {
				if (typeof callback == "function") {
					$.send(ENV.getProcessUrl("message","getBox"),{box:box},function(result) {
						if (result.success == true) {
							Message.boxes[box] = result.box;
							callback(result.box);
						}
					});
				} else {
					return null;
				}
			}
		},
		updateBox:function(box,data) {
			if (Message.boxes[box] === undefined) Message.boxes[box] = data;
			
			var $box = $("div[data-role=box][data-box="+box+"]",Message.$boxes);
			if ($box.length == 1) {
				var time = moment(data.latest).locale($("html").attr("lang"));
				var timetext = time.format("YYYY.MM.DD");
				if (time.format("YYYYMMDD") == moment().format("YYYYMMDD")) {
					timetext = time.format("LT");
				} else if (moment().diff(time) > 60 * 60 * 30 * 1000) {
					timetext = time.fromNow();
				}
				
				if ($box.data("member_hash") != data.member_hash) {
					var $photo = $("i.photo",$box);
					$photo.empty();
					var $title = $("b",$box);
					$title.empty();
					
					if (data.members.length == 1) {
						$photo.css("backgroundImage","url("+ENV.getModuleUrl("member","photo",data.members[0].idx,"profile.jpg")+")");
						var name = [data.members[0].nickname];
					} else {
						var name = [];
						var $wall = $("<div>");
						$wall.attr("data-role","wall-"+Math.min(data.members.length,4));
						for (var j=0, loopj=Math.min(data.members.length,4);j<loopj;j++) {
							$wall.append($("<i>").css("backgroundImage","url("+ENV.getModuleUrl("member","photo",data.members[j].idx,"profile.jpg")+")"));
							name.push(data.members[j].nickname);
						}
						$photo.append($wall);
					}
					
					$title.append($("<time>").html(timetext));
					$title.append(name.join(", "));
				} else {
					$("time",$("b",$box)).html(timetext);
				}
				
				$box.data("latest",data.latest);
				$box.data("member_hash",data.member_hash);
				
				$("small",$box).empty();
				$("small",$box).append($("<label>").html(data.unread));
				if (data.unread > 0) $("small",$box).addClass("new");
				else $("small",$box).removeClass("new");
				
				if (data.file != null) {
					if (typeof data.file == "object") {
						$("small",$box).append($("<i>").append($("<span>").html(iModule.getFileSize(data.file.size))).append(data.file.name));
					}
				}
				
				$("small",$box).append(data.message);
				Message.box.sort();
				
				if (JSON.stringify(Message.boxes[box].readeds) != JSON.stringify(data.readeds)) {
					var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts);
					$("i[data-role=unread]",$post).not(":empty").each(function() {
						var unread = Message.post.unread(data.readeds,parseInt($(this).attr("data-date"),10));
						$(this).html(unread == 0 ? "" : unread);
					});
				}
			}
			
			Message.boxes[box] = data;
		},
		getBoxes:function(callback) {
			if (Message.$boxes.data("loading") === true) return;
			Message.$boxes.data("loading",true);
			
			var $boxes = $("div[data-role=box]",Message.$boxes);
			if ($boxes.length == 0) {
				var position = 0;
			} else {
				var position = $boxes.last().data("latest");
			}
			if ($boxes.length == Message.$boxes.data("total")) {
				Message.$boxes.data("loading",false);
				if (typeof callback == "function") callback();
				return;
			}
			
			Message.$boxes.append($("<div>").attr("data-role","loading").html('<i class="mi mi-loading"></i>'));
			
			$.send(ENV.getProcessUrl("message","getBoxes"),{position:position},function(result) {
				Message.$boxes.data("loading",false);
				$("div[data-role=loading]",Message.$boxes).remove();
				
				if (result.success == true) {
					Message.$boxes.data("first",result.first);
					Message.$boxes.data("total",result.total);
					
					var ended = null;
					for (var i=0, loop=result.lists.length;i<loop;i++) {
						Message.boxes[result.lists[i].box] = result.lists[i];
						Message.box.print(result.lists[i].box);
						ended = result.lists[i].latest;
					}
					
					if (ended != null && ended >= result.first && Message.$boxes.height() > Message.$boxes.prop("scrollHeight") - 100) {
						Message.box.getBoxes(callback);
					} else {
						if (typeof callback == "function") callback();
					}
				}
			});
		},
		print:function(box) {
			if (typeof box == "object") {
				var data = box;
				var box = data.box;
			}
			
			var $box = $("div[data-role=box][data-box="+box+"]",Message.$boxes);
			if ($box.length == 1) return $box;
			
			var $box = $("<div>").attr("data-role","box").attr("data-box",box).data("latest",box.latest);
			$box.on("click",function() {
				Message.box.view($(this).attr("data-box"));
			});
			
			if (box === "new") {
				$box.append($("<i>").addClass("photo"));
				$box.append($("<b>").text("새 메시지 작성"));
				Message.$searchbox.after($box);
			} else {
				var data = data ? data : Message.box.getBox(box);
				if (data === null) return;
				
				$box.data("latest",data.latest);
				$box.data("member_hash",data.member_hash);
				
				var $photo = $("<i>").addClass("photo");
				$box.append($photo);
				var $title = $("<b>");
				$box.append($title);
				var $post = $("<small>");
				$box.append($post);
				
				Message.$boxes.append($box);
				
				var time = moment(data.latest).locale($("html").attr("lang"));
				var timetext = time.format("YYYY.MM.DD");
				if (time.format("YYYYMMDD") == moment().format("YYYYMMDD")) {
					timetext = time.format("LT");
				} else if (moment().diff(time) > 60 * 60 * 30 * 1000) {
					timetext = time.fromNow();
				}
				
				if (data.members.length == 1) {
					$photo.css("backgroundImage","url("+ENV.getModuleUrl("member","photo",data.members[0].idx,"profile.jpg")+")");
					var name = [data.members[0].nickname];
				} else {
					var name = [];
					var $wall = $("<div>");
					$wall.attr("data-role","wall-"+Math.min(data.members.length,4));
					for (var j=0, loopj=Math.min(data.members.length,4);j<loopj;j++) {
						$wall.append($("<i>").css("backgroundImage","url("+ENV.getModuleUrl("member","photo",data.members[j].idx,"profile.jpg")+")"));
						name.push(data.members[j].nickname);
					}
					$photo.append($wall);
				}
				
				$title.append($("<time>").html(timetext));
				$title.append(name.join(", "));
				
				$post.empty();
				$post.append($("<label>").html(data.unread));
				if (data.unread > 0) $post.addClass("new");
				else $post.removeClass("new");
				
				if (data.file != null) {
					if (typeof data.file == "object") {
						$post.append($("<i>").append($("<span>").html(iModule.getFileSize(data.file.size))).append(data.file.name));
					}
				}
				
				$post.append(data.message);
			}
			
			return $box;
		},
		sort:function() {
			var $items = $("div[data-role=box][data-box!=new]",Message.$boxes);
			[].sort.call($items,function(left,right) {
				return $(left).data("latest") < $(right).data("latest");
			});
			$items.each(function(){
				Message.$boxes.append(this);
			});
		},
		view:function(box,scroll) {
			$("div[data-role=box][data-box]",Message.$boxes).removeClass("selected");
			var $box = $("div[data-role=box][data-box="+box+"]",Message.$boxes);
			var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts);
			if ($box.length == 0) {
				if (box == "new") {
					Message.title.print("new");
					var $box = Message.box.print("new");
					$box.addClass("selected");
				} else if ($post.length == 0) {
					Message.box.getBox(box,function(box) {
						Message.title.print(box.box);
						
						var $latest = $("div[data-role=box][data-box!=new]",Message.$boxes);
						if ($latest.length > 0) {
							if ($latest.eq(0).data("latest") < box.latest) {
								var $box = Message.box.print(box);
								$box.addClass("selected");
							}
						}
					});
				}
			} else {
				$box.addClass("selected");
				Message.title.print(box);
			}
			
			Message.post.view(box,scroll);
			
			if ($post.length == 1 && $post.is(":visible") == true) return;
		}
	},
	/**
	 * 메시지
	 */
	post:{
		init:function() {
			
		},
		print:function(box) {
			var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts).data("scroll",0);
			if ($post.length == 0) {
				var $post = $("<div>").attr("data-role","post").attr("data-box",box);
				$post.on("scroll",function() {
					if ($(this).data("scroll") > $(this).scrollTop() && $(this).scrollTop() < 100) {
						Message.post.getPosts($(this).attr("data-box"),"prev");
					}
					
					if ($(this).data("scroll") < $(this).scrollTop() && $(this).scrollTop() + $(this).height() > $(this).prop("scrollHeight") - 100) {
						Message.post.getPosts($(this).attr("data-box"),"next");
					}
					$(this).data("scroll",$(this).scrollTop());
				});
				Message.$posts.append($post);
			}
			
			return $post;
		},
		view:function(box,scroll) {
			var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts);
			if ($post.length == 0) {
				Message.post.print(box);
			}
			
			$("div[data-role=post][data-box]",Message.$posts).hide();
			$("div[data-role=post][data-box="+box+"]",Message.$posts).show();
			
			var state = location.href.split(Message.getUrl(false)).pop().split("?").shift().split("/");
			var current = state[1];
			
			if (current != box) {
				history.pushState({},null,Message.getUrl(box,false));
			}
			
			Message.post.getPosts(box,"next",scroll);
			Message.inputbox.enable();
		},
		getPosts:function(box,dir,scroll) {
			var dir = dir ? dir : "next";
			
			if (box == "new") {
				
			} else {
				var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts);
				if ($post.length == 0) {
					var $post = Message.post.print(box);
				}
				
				var position = null;
				var $items = $("div.balloon",$post);
				if ($items.length == 0) {
					dir = "next";
				} else {
					if (dir == "next") {
						position = $items.last().data("reg_date");
						if (Message.box.getBox(box).latest == position) return;
					} else {
						position = $items.first().data("reg_date");
						if (Message.box.getBox(box).first == position) return;
					}
				}
				
				if ($post.data(dir) === true) return;
				
				var $loading = $("<div>").attr("data-role","loading").addClass(dir).html('<i class="mi mi-loading"></i>');
				if (dir == "next") {
					$post.append($loading);
				} else {
					$post.prepend($loading);
					$post.scrollTop($post.data("scroll") + $loading.outerHeight());
				}
				$post.data(dir,true);
				
				$.send(ENV.getProcessUrl("message","getPosts"),{box:box,dir:dir,position:position},function(result) {
					if (result.success == true) {
						Message.box.updateBox(box,result.box);
						
						if (dir == "next") {
							$loading.remove();
						} else {
							var scrollHeight = $post.prop("scrollHeight");
							$loading.remove();
						}
						
						for (var i=0, loop=result.lists.length;i<loop;i++) {
							var $item = dir == "next" ? $("div[data-role=item]",$post).last() : $("div[data-role=item]",$post).first();
							if ($item.length == 0 || $item.data("sender") != result.lists[i].sender) {
								var $item = $("<div>").attr("data-role","item").attr("data-mode",result.lists[i].mode).data("sender",result.lists[i].sender);
								if (dir == "next") $post.append($item);
								else $post.prepend($item);
								
								var $photo = $("<i>").addClass("photo").css("backgroundImage","url("+result.lists[i].photo);
								$item.append($photo);
								
								var $name = $("<b>").html(result.lists[i].nickname);
								$item.append($name);
								$item.append($("<div>").addClass("contents"));
							}
							
							var $contents = $("div.contents",$item);
							
							var $balloon = $("<div>").addClass("balloon").attr("data-position",result.lists[i].reg_date).data(result.lists[i]);
							var $message = $("<div>").addClass("message");
							
							if (result.lists[i].file != null) {
								if (typeof result.lists[i].file == "object") {
									var $file = $("<a>").addClass(result.lists[i].file.type);
									if (result.lists[i].file.type == "image") {
										$file.append($("<img>").attr("src",result.lists[i].file.thumbnail));
										$file.height(Math.floor(result.lists[i].file.height * 240 / result.lists[i].file.width)+"px");
									} else {
										$file.append($("<i>").css("backgroundImage","url("+result.lists[i].file.icon));
										$file.append($("<div>").html('<b>'+result.lists[i].file.name+'</b><small>'+iModule.getFileSize(result.lists[i].file.size)+'</small>'));
									}
									if (result.lists[i].message) $file.addClass("message");
									$message.append($file);
								}
							}
							
							$message.append(result.lists[i].message);
							$balloon.append($message);
							
							var $info = $("<div>").addClass("info");
							var unread = Message.post.unread(result.box.readeds,result.lists[i].reg_date);
							var $count = $("<i>").attr("data-role","unread").attr("data-date",result.lists[i].reg_date).html(unread == 0 ? "" : unread);
							$info.append($count);
							
							var time = moment(result.lists[i].reg_date).locale($("html").attr("lang"));
							var timetext = time.format("YYYY.MM.DD");
							if (time.format("YYYYMMDD") == moment().format("YYYYMMDD")) {
								timetext = time.format("LT");
							} else if (moment().diff(time) > 60 * 60 * 30 * 1000) {
								timetext = time.fromNow();
							}
							
							var $time = $("<time>").html(timetext);
							$info.append($time);
							
							if (result.lists[i].mode == "send") $balloon.prepend($info);
							else $balloon.append($info);
							
							if (dir == "next") $contents.append($balloon);
							else $contents.prepend($balloon);
						}
						
						if (dir == "next") {
							if (scroll) Message.post.scrollTo(box,scroll);
							else if ($post.data("loaded") !== true) Message.post.scrollTo(box,result.position);
						} else {
							$post.scrollTop($post.data("scroll") + $post.prop("scrollHeight") - scrollHeight);
						}
						$post.data(dir,false);
						$post.data("loaded",true);
					}
				});
			}
		},
		unread:function(readeds,reg_date) {
			var count = 0;
			for (var i=0, loop=readeds.length;i<loop;i++) {
				if (reg_date <= readeds[i]) return i;
				else count++;
			}
			return readeds.length;
		},
		scrollTo:function(box,scroll) {
			var $post = $("div[data-role=post][data-box="+box+"]",Message.$posts);
			var $balloon = $("div.balloon[data-position="+scroll+"]",$post);
			if ($balloon.length == 0) $balloon = $("div.balloon",$post).first();
			if ($balloon.length == 0) return;
			var top = $balloon.offset().top - $post.offset().top + $post.scrollTop() - 30;
			$post.scrollTop(top);
		}
	},
	/**
	 * 메시지 받는 사람
	 */
	receiver:{
		timer:null,
		init:function() {
			$("input",Message.$receiver).on("keydown",function(e) {
				var $input = $(this);
				var $parent = $(this).parent();
				if (e.keyCode == 38 || e.keyCode == 40) {
					e.preventDefault();
				
					var $lists = $("ul",$parent);
					if ($lists.length == 0) return;
					
					var $items = $("li[data-role=user]",$lists);
					if ($items.length == 0) return;
					
					var index = $items.index($items.filter(".selected"));
					
					if (e.keyCode == 38 && index > 0) index--;
					if (e.keyCode == 40 && index < $items.length - 1) index++;
					if (!~index) index = 0;
					
					$items.removeClass("selected");
					$items.eq(index).addClass("selected");
					
					if (e.keyCode == 40 && $items.eq(index).position().top + $items.eq(index).height() > $lists.height()) {
						$lists.scrollTop($lists.scrollTop() + $items.eq(index).position().top + $items.eq(index).height() - $lists.height());
					}
					
					if (e.keyCode == 38 && $items.eq(index).position().top < 0) {
						$lists.scrollTop($lists.scrollTop() + $items.eq(index).position().top);
					}
				}
				
				if (e.keyCode == 13) {
					if ($("ul > li[data-role=user].selected",$parent).length > 0) {
						$input.data("last","");
						$input.val("");
						Message.receiver.addUser($("ul > li[data-role=user].selected",$parent).eq(0).data("user"));
						$("ul",$parent).remove();
					
						e.preventDefault();
					}
				}
				
				if (e.keyCode == 8) {
					if ($input.val().length == 0) {
						var $names = $("div[data-role=user]",Message.$receiver);
						if ($names.length > 0) {
							$names.last().remove();
						}
					}
				}
			});
			
			$("input",Message.$receiver).on("focus",function() {
				if (Message.timer != null) {
					clearTimeout(Message.timer);
					Message.timer = null;
				}
				Message.timer = setTimeout(Message.receiver.getUsers,100,$(this));
			});
			
			$("input",Message.$receiver).on("blur",function() {
				if (Message.timer != null) {
					clearTimeout(Message.timer);
					Message.timer = null;
				}
				
				setTimeout(function($input) {
					$input.data("last","");
					var $parent = $input.parent();
					$("ul",$parent).remove();
				},100,$(this));
			});
		},
		/**
		 * 받는사람 검색
		 */
		getUsers:function($input) {
			if (Message.receiver.timer != null) {
				clearTimeout(Message.receiver.timer);
				Message.receiver.timer = null;
			}
				
			var $parent = $input.parent();
			
			if ($input.val().length > 0) {
				var $lists = $("ul",$parent);
				if ($lists.length == 0) {
					var $lists = $("<ul>");
					$lists.append($("<li>").addClass("message").html('<i class="mi mi-loading"></i>'));
					$parent.append($lists);
				}
				
				if ($input.data("last") != $input.val()) {
					$input.data("last",$input.val());
					$lists.empty();
					$lists.append($("<li>").addClass("message").html('<i class="mi mi-loading"></i>'));
					
					$.send(ENV.getProcessUrl("message","getUsers"),{keyword:$input.val()},function(result) {
						$lists.empty();
						if (result.success == true && result.users.length > 0) {
							for (var i=0, loop=result.users.length;i<loop;i++) {
								var $user = $("<li>").attr("data-role","user").data("user",result.users[i]);
								var $photo = $("<i>").addClass("photo");
								$photo.css("backgroundImage","url(" + ENV.getModuleUrl("member","photo",result.users[i].idx,"profile.jpg") + ")");
								$user.append($photo);
								$user.append(result.users[i].display);
								$user.on("click",function() {
									Message.receiver.addUser($(this).data("user"));
									$input.val("");
									$input.data("last","");
									$input.focus();
								});
								$lists.append($user);
							}
						} else {
							$lists.append($("<li>").addClass("message").html("검색된 회원이 없습니다."));
						}
						
						Message.receiver.timer = setTimeout(Message.receiver.getUsers,100,$input);
					});
				} else {
					Message.receiver.timer = setTimeout(Message.receiver.getUsers,100,$input);
				}
			} else {
				$("ul",$parent).remove();
				$input.data("last","");
				Message.receiver.timer = setTimeout(Message.receiver.getUsers,100,$input);
			}
		},
		/**
		 * 받는사람 추가
		 */
		addUser:function(user) {
			var $input = $("div[data-role=input]",Message.$receiver);
			
			var $user = $("<div>").attr("data-role","user").attr("tabindex",1).data("user",user);
			$user.on("keyup",function(e) {
				if (e.keyCode == 8 || e.keyCode == 46) {
					$user.remove();
					$("input",$input).focus();
				}
				e.preventDefault();
			});
			$user.text(user.display);
			$input.before($user);
		}
	},
	/**
	 * 메시지 입력폼
	 */
	inputbox:{
		file:null,
		init:function() {
			$("input[type=file]",Message.$form).on("change",function(e) {
				if (e.target.files.length > 0) {
					var file = e.target.files[0];
					Message.inputbox.file = file;
					Message.inputbox.file.failCount = 0;
					
					$("div[data-role=file]",Message.$inputbox).empty();
					
					var $label = $("<label>");
					var $button = $("<button>").attr("type","button").html('<i class="mi mi-close-bold"></i>');
					$button.on("click",function() {
						$("input[type=file]",Message.$form).reset();
						Message.inputbox.file = null;
						$("div[data-role=file]",Message.$inputbox).empty();
						Message.inputbox.button();
						
						Message.$posts.css("paddingBottom",Message.$inputbox.outerHeight());
						Message.inputbox.button();
					});
					$label.append($button);
					
					$label.append('<span>'+iModule.getFileSize(file.size)+'</span>'+file.name);
					$("div[data-role=file]",Message.$inputbox).append($label);
					
					Message.inputbox.button();
					
					Message.$posts.css("paddingBottom",Message.$inputbox.outerHeight());
					Message.inputbox.button();
					
					return;
					/*
					$("button[type=submit]",$form).status("loading");
					$("button[data-action=upload]",$form).html('<i class="mi mi-loading"></i> 업로드중... (0%)').disable();
					
					var file = e.target.files[0];
					
					var draft = {};
					draft.name = file.name;
					draft.size = file.size;
					draft.type = file.type;
					
					var params = {};
					params.file = JSON.stringify(draft);
					params.target = "download";
					
					if ($("input[name=file]",$form).val()) params.replace = $("input[name=file]",$form).val();
					
					$.send(ENV.getProcessUrl("moimz","draft"),params,function(result) {
						if (result.success == true) {
							file.idx = result.file.idx;
							file.code = result.file.code;
							file.mime = result.file.mime;
							file.uploaded = result.file.uploaded;
							file.extension = result.file.extension;
							file.status = result.file.status;
							
							Moimz.download.upload(file);
						}
					});
					*/
				}
			});
			
			$("textarea",Message.$inputbox).on("keyup",function() {
				$(this).outerHeight(40);
				
				if ($(this).prop("scrollHeight") > $(this).outerHeight()) {
					$(this).outerHeight(Math.min(100,$(this).prop("scrollHeight")));
				}
				
				Message.$posts.css("paddingBottom",Message.$inputbox.outerHeight());
				Message.inputbox.button();
			}).on("keydown",function(e) {
				if (e.shiftKey == false && e.keyCode == 13) {
					Message.inputbox.send();
					e.preventDefault();
				}
			});
		},
		send:function(filehash) {
			var filehash = filehash ? filehash : null;
			var $textarea = $("textarea",Message.$inputbox);
			var $post = $("div[data-role=post]:visible",Message.$posts);
			if ($post.length != 1) return;
			
			var message = $textarea.val();
			if (Message.inputbox.file == null && message.length == 0 && filehash == null) return;
			
			var box = $post.attr("data-box");
			if (box == "new") {
				var midxes = [];
				var $users = $("div[data-role=user]",Message.$receiver);
				$users.each(function() {
					midxes.push($(this).data("user").idx);
				});
				$users.remove();
				if (midxes.length == 0) return;
			} else {
				var midxes = [];
			}
			
			if (filehash == null) $("div",Message.$progress).width("0%").show();
			Message.inputbox.disable();
			
			if (Message.inputbox.file != null) {
				var params = {};
				params.name = Message.inputbox.file.name;
				params.size = Message.inputbox.file.size;
				params.type = Message.inputbox.file.type;
				
				$.send(ENV.getProcessUrl("message","draft"),params,function(result) {
					if (result.success == true) {
						Message.inputbox.file.hash = result.hash;
						Message.inputbox.file.uploaded = result.uploaded;
						Message.inputbox.file.mime = result.mime;
						Message.inputbox.upload();
					}
				});
				
				return;
			}
			
			$.send(ENV.getProcessUrl("message","send"),{box:box,midxes:midxes,message:message,file:filehash},function(result) {
				if (result.success == true) {
					$("div",Message.$progress).animate({width:"100%"},function() {
						$("div[data-role=box][data-box=new]",Message.$boxes).remove();
						$(this).fadeOut();
						Message.box.print(result.box);
						Message.box.updateBox(box,result.box);
						Message.box.view(result.box.box,result.box.latest);
					
						Message.inputbox.enable();
						
						$textarea.val("");
						$textarea.outerHeight(40);
						
						Message.$posts.css("paddingBottom",Message.$inputbox.outerHeight());
						Message.inputbox.button();
						
						$("div[data-role=file]",Message.$inputbox).empty();
					});
				}
			});
		},
		upload:function() {
			var file = Message.inputbox.file;
			if (file == null) return Message.inputbox.send();
			
			var chunkSize = 2 * 1000 * 1000;
			file.chunk = file.size > file.uploaded + chunkSize ? file.uploaded + chunkSize : file.size;
			
			$.ajax({
				url:ENV.getProcessUrl("message","upload")+"?hash="+file.hash,
				method:"POST",
				contentType:file.mime,
				headers:{
					"Content-Range":"bytes " + file.uploaded + "-" + (file.chunk - 1) + "/" + file.size
				},
				xhr:function() {
					var xhr = $.ajaxSettings.xhr();
	
					if (xhr.upload) {
						xhr.upload.addEventListener("progress",function(e) {
							if (e.lengthComputable) {
								$("div",Message.$progress).width(Math.ceil((file.uploaded + e.loaded) / file.size * 90)+"%");
							}
						},false);
					}
	
					return xhr;
				},
				processData:false,
				data:file.slice(file.uploaded,file.chunk)
			}).done(function(result) {
				if (result.success == true) {
					file.failCount = 0;
					
					if (file.chunk == file.size) {
						Message.inputbox.file = null;
						$("input[type=file]",Message.$form).reset();
						Message.inputbox.send(result.hash);
					} else {
						file.uploaded = result.uploaded;
						Message.inputbox.upload();
					}
				} else {
					if (file.failCount < 3) {
						file.failCount++;
						Message.inputbox.upload();
					} else {
						
					}
				}
			}).fail(function() {
				if (file.failCount < 3) {
					file.failCount++;
					Message.inputbox.upload();
				}
			});
		},
		enable:function() {
			Message.$inputbox.removeClass("disable");
			$("textarea",Message.$inputbox).enable();
			$("button[data-action=attachment]",Message.$inputbox).enable();
			Message.inputbox.button();
		},
		disable:function() {
			Message.$inputbox.addClass("disable");
			$("textarea",Message.$inputbox).disable();
			$("button[data-action=attachment]",Message.$inputbox).disable();
			$("button[data-action=send]",Message.$inputbox).disable();
			Message.inputbox.button(true);
		},
		button:function(is_force) {
			if (is_force === true) {
				$("button[data-action=send]",Message.$inputbox).disable();
				return;
			}
			
			if ($("textarea",Message.$inputbox).val().length > 0 || Message.inputbox.file !== null) {
				$("button[data-action=send]",Message.$inputbox).enable();
			} else {
				$("button[data-action=send]",Message.$inputbox).disable();
			}
		}
	},
	reload:function() {
		var $post = $("div[data-role=post][data-box]:visible",Message.$posts);
		var box = $post.length == 1 ? $post.attr("data-box") : null;
		$.send(ENV.getProcessUrl("message","getRecently"),{box:box},function(result) {
			if (result.success == true) {
				var latest = null;
				for (var i=0, loop=result.boxes.length;i<loop;i++) {
					Message.box.updateBox(result.boxes[i].box,result.boxes[i]);
				}
				
				if (box != null) {
					Message.post.getPosts(box,"next",Message.box.getBox(box).latest);
				}
			}
			
			setTimeout(Message.reload,30000);
		});
	}
};