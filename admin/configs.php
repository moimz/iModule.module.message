<?php
/**
 * 이 파일은 iModule 메시지모듈의 일부입니다. (https://www.imodules.io)
 *
 * 메시지모듈 설정을 위한 설정폼을 생성한다.
 * 
 * @file /modules/message/admin/configs.php
 * @author Arzz (arzz@arzz.com)
 * @license GPLv3
 * @version 3.0.0
 * @modified 2019. 4. 21.
 */
if (defined('__IM__') == false) exit;
?>
<script>
new Ext.form.Panel({
	id:"ModuleConfigForm",
	border:false,
	bodyPadding:"10 10 5 10",
	width:500,
	fieldDefaults:{labelAlign:"right",labelWidth:100,anchor:"100%",allowBlank:true},
	items:[
		new Ext.form.FieldSet({
			title:Message.getText("admin/configs/form/default_setting"),
			items:[
				Admin.templetField(Message.getText("admin/configs/form/templet"),"templet","module","message",false)
			]
		})
	]
});
</script>