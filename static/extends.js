(function (w) {
	w.Extends = {
		tabNavs:[],
		init:function(){
			var navs = $('.extends-tabs-nav');
			if(navs.length > 0){
				$.each(navs,function(k,nav){
					Extends.initTabs(nav,k);
				});
			}
			if($('#file-list').length > 0){
				Extends.initThumbnail();
				$('#file-list').on('mouseover mouseout','li',function(e){Extends.showSetThumbnailBtn(this,e)});
			}
			$('#smilies-list > span').click(function(){
				Extends.insertToEditor($(this).data('tag'));
			});
		},
		insertToEditor:function(html){
			var textarea = $('#text'), sel = textarea.getSelection(),
			offset = (sel ? sel.start : 0) + html.length;
			textarea.replaceSelection(html);
        	textarea.setSelection(offset, offset);
		},
		initTabs:function(nav,tabId){
			var tabItemName = $(nav).attr('for-tab-item');
			var tabItems = $('.extends-tab-item[tab-item-name='+ tabItemName +']');
			Extends.tabNavs.push({
				'tabId':tabId,
				'tabNavBtn':nav,
				'tabItemName':tabItemName,
				'tabItems':tabItems
			});
			
			$(nav).find('li').on('click',function(){
				Extends.showTab(tabId,$(this).attr('for'));
			});
			Extends.showTab(0,'base');
		},
		showTab:function(tabId,index){
			$.each(Extends.tabNavs,function(key,tabNav){
				if(tabNav.tabId == tabId){
					$(tabNav.tabNavBtn).find('li[for='+index+']').addClass('current').siblings().removeClass('current');
					Extends.showTabItem(tabNav.tabItems,index);
				}
			});
		},
		showTabItem:function(tabItems,index){
			$.each(tabItems,function(k,v){
				if(index == $(v).attr('tab')){
					$(v).show();
				}else{
					$(v).hide();
				}
			});
		},
		initThumbnail:function(){
			var url = $('#thumbnail').val();
			$.each($('#file-list').find('li'),function(k,v){
				if( url == $(v).data('url')){
					$('<span class="thumbnail-default">已设为缩略图</span>').appendTo($(v).find('.info'));
				}
			});
			$('#thumbnail').on('propertychange input',function(){
				$('#thumbnail-preview').attr('src',$(this).val());
			});
		},
		showSetThumbnailBtn:function(el,e){
			if( undefined === $(el).data('image') || 1 != $(el).data('image')){
				return;
			}
			var isDefault = ($(el).find('.thumbnail-default').length > 0) ? true : false;
			var setThumbnail = function(url){
				$('#thumbnail').val(url);
				$('#thumbnail-preview').attr('src',url);
			}

			if($(el).find('.thumbnail').length == 0){
				var title = isDefault ? '取消缩略图' : '设为缩略图';
				$('<a href="###" class="thumbnail">'+title+'</a>').on('click',function(){
					if(isDefault){
						setThumbnail('');
						$(this).parent().find('.thumbnail-default').remove();
						$(this).parent().find('.thumbnail').remove();
					}else{
						var url = $(this).parents('li').data('url');
						setThumbnail(url);
						$('#file-list').find('.thumbnail-default').parent().find('.thumbnail').remove();
						$('#file-list').find('.thumbnail-default').remove();
						$(this).after('<span class="thumbnail-default">已设为缩略图</span>');
						$(this).parent().find('.thumbnail').remove();
					}
				}).appendTo($(el).find('.info'));
			}
			if(e.type == 'mouseover'){
				$(el).find('.thumbnail').show();
			}else{
				$(el).find('.thumbnail').hide();
			}
		}
	}
})(window);

window.onload = function(){
	Extends.init();
};