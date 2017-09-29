<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho扩展插件
 * 
 * @package Textends 
 * @author 绛木子
 * @version 1.0.0
 * @link http://lixianhua.com
 */
class Textends_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 安装附加表
		$result = self::installDb();
        // 重新设置select的字段
		Typecho_Plugin::factory('Widget_Archive')->select = array('Textends_Contents', 'select');
		// 增加浏览数
        Typecho_Plugin::factory('Widget_Archive')->singleHandle = array('Textends_Contents', 'viewCounter');
		// content filter
		Typecho_Plugin::factory('Widget_Abstract_Contents')->filter = array('Textends_Contents', 'filter');
		Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Textends_Contents', 'contentEx');

		// 编辑扩展字段
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('Textends_Contents', 'addContentsExtends');
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('Textends_Contents', 'addContentsExtends');
		// 编辑器设置缩略图字段
		Typecho_Plugin::factory('admin/write-post.php')->option = array('Textends_Plugin', 'addThumbnailOption');
		Typecho_Plugin::factory('admin/write-page.php')->option = array('Textends_Plugin', 'addThumbnailOption');
		
		// 后台附加样式
        Typecho_Plugin::factory('admin/header.php')->header = array('Textends_Plugin', 'addAdminHeader');
		
        // 增加action
		Helper::addAction('textends','Textends_Action');
        // 增加图片处理路由
        Helper::addRoute('thumbnail', '/thumbnail/[params:string]','Textends_Thumbnail','render');
		
		return _t($result);
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeAction('textends');
        Helper::removeRoute('thumbnail');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){}

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

	/**
     * 站点Url替换为CDN地址，需在主题中添加设置
     * 
     * @access public
     * @param string $url 待替换的地址
     * @return string
     */
	public static function cdnReplace($url){
		$siteUrl = Helper::options()->siteUrl;
		$cdn = Helper::options()->cdn;
		if(empty($cdn)){
			return $url;
		}
        if(false === strpos($url,'http')){
            $url = rtrim($cdn,'/') . '/' . ltrim($url,'/');
        }elseif(false !== strpos($url,$siteUrl)){
            $url = str_replace(rtrim($siteUrl,'/').'/',rtrim($cdn,'/').'/',ltrim($url,'/'));
        }
        return $url;
    }

	/**
     * 显示缩略图，需在主题中添加设置
     * 
     * @access public
	 * @param Widget_Archive $archive
	 * @param array|string $thumbnailOptions 缩略图显示配置项
     * @return mix
     */
    public static function thumbnail($archive, $thumbnailOptions=null){
		$options = Helper::options();
		$thumbnailOptions = new Typecho_Config($thumbnailOptions);
		$thumbnailOptions->setDefault(array(
			'ident'=>$options->thumbnailIdent,		// 分隔符
			'width'=>$options->thumbnailW,			// 缩略图宽度
			'height'=>$options->thumbnailH,			// 缩略图高度
			'quality'=>$options->thumbnailQ,		// 缩略图质量
			'mode'=>$options->thumbnailMode,		// 模式
            'default'=>$options->thumbnailDefault, 	// 默认缩略图
			'format'=>'<img src="{thumbnail}" title="{title}" width="{width}" height="{height}">',	// 缩略图显示格式化模板
			'output'=>true,	// 是否直接显示 默认直接显示
			'link'=>false	// 是否替换模板 默认替换
		));
		
		$thumbnail = '';
		if(is_object($archive)){
			$thumbnail = empty($archive->thumbnail) ? $thumbnailOptions->default : $archive->thumbnail;
		}else{
			$thumbnail = empty($archive) ? $thumbnailOptions->default : $archive;
		}
		if(empty($thumbnail)){
			return false;
		}
		$path = $thumbnail;
		if(false !== strpos($thumbnail,'http') && false !== strpos($thumbnail,$options->siteUrl)){
			$thumbnail = $path = substr($thumbnail,strlen($options->siteUrl));
			if(false !== strpos($thumbnail,'usr/uploads/')){
				$path = substr($thumbnail,strlen('usr/uploads/'));
			}	
		}

		$thumbnailAction = Typecho_Common::url('thumbnail', $options->index);
		$thumbnailUrl = str_replace(array('{thumbnail}','{image}','{path}','{mode}','{ident}','{width}','{height}','{quality}'),
				array($thumbnailAction,$thumbnail,$path,$thumbnailOptions->mode,$thumbnailOptions->ident,$thumbnailOptions->width,$thumbnailOptions->height,$thumbnailOptions->quality),
				$options->thumbnailFormat);

		if(is_string($archive)){
			$permalink = $title = $slug = $cid ='';
		}else{
			$permalink = $archive->permalink;
			$title = $archive->title;
			$slug = $archive->slug;
			$cid = $archive->cid;
		}
		$width = $thumbnailOptions->width;
		$height = $thumbnailOptions->height;
		
		if(!$thumbnailOptions->link){
			$output = str_replace(array('{permalink}','{title}','{slug}','{cid}','{thumbnail}','{width}','{height}'),
					array($permalink,$title,$slug,$cid,$thumbnailUrl,$width,$height),
					$thumbnailOptions->format);
		}else{
			$output = $thumbnailUrl;
		}
		if(!$thumbnailOptions->output){
			return $output;
		}
		echo $output;
		return true;
	}

    
	/**
	 * 显示内容
	 * @param string $options 内容参数
	 * @return  Textends_Widget_Contents
	 *
	 * options支持的参数介绍
	 * 
	 * type:内容类型 post|page
	 * orderby:排序字段 支持viewsNum,likesNum
	 * sort:排序方式 ASC|DESC
	 * cid:内容id，支持数组或以逗号“,”分割的多个id
	 * uid:内容作者id，支持数组或以逗号“,”分割的多个uid
	 * mid:分类或标签id，支持数组或以逗号“,”分割的多个mid
	 * children:是否显示分类下子分类的文章
	 * day:几天以内的内容，支持小数
	 * limit:显示条数，为“0”时表示查询所有数据
	 */
	public static function contents($options = ''){
		return Typecho_Widget::widget('Textends_Widget_Contents@te_con_'.md5(is_array($options) ? json_encode($options) : $options),$options);
	}

	/**
	 * 显示评论
	 * @param string $options 评论参数
	 * @return Textends_Widget_Comments
	 *
	 * options支持的参数介绍
	 * 
	 * cid: 内容id
	 * uid: 用户id
	 * sort: 排序字段
	 * desc: 是否从高到低排列 true|false
	 * page: 分页ID
	 * limit: 显示条数
	 * status: 评论状态
	 */
	public static function comments($options = ''){
		return Typecho_Widget::widget('Textends_Widget_Comments@te_com_'.md5(is_array($options) ? json_encode($options) : $options),$options);
	}
	
	/**
     * 评论作者列表
     * 
     * @param array|string $options 配置字符串
	 * @param string $format 格式化输出模板
     * @return void
	 * 
	 * options 支持的参数
	 * sort: 排序字段
	 * desc: 是否逆序排列
     */
	public static function commentAuthor($options='',$format='<a href="{url}" title="{author}留下{num}条留言" rel="external nofollow"><img class="avatar" src="{avatar}" width="32"></a>'){
		$db = Typecho_Db::get();
		$options = new Typecho_Config($options);
		$options->setDefault(array('sort'=>'commentTotal','desc'=>true));
		
		$select = $db->select(array('COUNT(mail)'=>'commentTotal'),'author','url','mail')->from('table.comments')
			->where('status = ?','approved')
			->where('authorId = ?','0')
			->where('type = ?','comment')
			->group('mail')
			->order($options->sort, $options->desc ? Typecho_Db::SORT_DESC : Typecho_Db::SORT_ASC);
		if(isset($options->pageSize)){
			$select->limit($options->pageSize);
		}
		$counts = $db->fetchAll($select);
		$html = '';
		foreach($counts as $count){
			$avatar = self::gravatarUrl($count['mail']);
			$count['url'] = empty($count['url']) ? '' : $count['url'];
			$html .= str_replace(array('{url}','{author}','{mail}','{num}','{avatar}'),
			array($count['url'],$count['author'],$count['mail'],$count['commentTotal'],$avatar),$format);
		}
		echo $html;
	}

    /**
	 * 显示标签云
	 * @param string $options 标签参数
	 * @param string $format 标签显示模板
	 * 
	 * options参数介绍:
	 *
	 * sort: 排序字段 默认为 count 
	 * ignoreZeroCount: 是否过滤空标签 true|false 默认不过滤
	 * desc: 是否从高到低排列 true|false
	 * limit: 显示条数，默认显示全部
	 * smallest: 标签最小字体大小
	 * largest: 标签最大字体大小
	 * unit: 字体大小的单位
	 */
	public static function tags($options=null, $format='<a href="{permalink}" style="{fontsize};color:{color};" title="{count}篇文章">{name}</a>'){

		Typecho_Widget::widget('Widget_Metas_Tag_Cloud', $options)->to($tags);

		$options = new Typecho_Config($options);
		$options->setDefault(array('smallest'=>8, 'largest'=>22, 'unit'=>'pt','output'=>true));
		if(!$options->output){
			return $tags;
		}
		$list = $counts = array();
		while($tags->next()){
			$list[] = array(
				'mid'=>$tags->mid,
				'name'=>$tags->name,
				'permalink'=>$tags->permalink,
				'count'=>$tags->count,
			);
			$counts[] = $tags->count;
		}
		if(empty($counts)){
			return false;
		}
		$min_count = min($counts);
		$spread = max($counts) - $min_count;
		
		$options = new Typecho_Config($options);
		$options->setDefault(array(
			'smallest' => 8, 'largest' => 22, 'unit' => 'pt'
		));
		
		if ( $spread <= 0 ){
			$spread = 1;
		}
			
		$font_spread = $options->largest - $options->smallest;
		if ( $font_spread < 0 ){
			$font_spread = 1;
		}
		$font_step = $font_spread / $spread;
		$html = '';
		$colors=array('#ff3300','#0517c2','#0fc317','#e7cc17','#601165','#ffb900','#f74e1e','#00a4ef','#7fba00');
		shuffle($list);
		foreach($list as $tag){
			$color = $colors[rand(0,8)];
			$fontsize = 'font-size:'.( $options->smallest + (( $tag['count'] - $min_count ) * $font_step) ).$options->unit;
			$html .= str_replace(array('{name}','{permalink}','{count}','{fontsize}','{color}'),
			array($tag['name'],$tag['permalink'],$tag['count'],$fontsize,$color),$format);
		}
		echo $html;
		return true;
	}

    /**
	 * 内容归档
	 * @param string $options 归档参数
	 * 
	 * options参数介绍
	 * 
	 * desc: 是否按时间倒序排列
	 * wrapClass: 归档元素的class
	 * monthClass: 按月归档的class
	 * monthTitle: 按月归档的标题，为空则不显示；支持日期格式化参数
	 * monthTitleTag: 按月归档的标题的标签
	 * listTag: 月内容列表的标签
	 * listClass: 月内容列表的class
	 * listFormat: 单条内容显示模板
	 * dateFormat: 显示日期时的日期格式
	 * output: 是否直接输出 true|false
	 */
	public static function archives($options=null){
		$options = new Typecho_Config($options);
		$options->setDefault(array(
			'desc'=>true ,'wrapClass'=>'archives', 'monthClass'=>'archive-month', 'monthTitle'=>'Y年m月', 'monthTitleTag'=>'h2', 'listTag'=>'ul', 'listClass'=>'archive-list',
			'dateFormat'=>'Y-m-d H:i:s', 'listFormat'=>'<li>{day}日 <a href="{permalink}">{title}</a></li>', 'output'=>true
		));
		$stat = Typecho_Widget::widget('Widget_Stat');
		$sort = $options->desc ? Typecho_Db::SORT_DESC : Typecho_Db::SORT_ASC;
		self::contents('limit='.$stat->publishedPostsNum.'&sort='.$sort)->to($archives);
		$year=0; $mon=0; $i=0; $j=0;
		$output = '<div class="'.$options->wrapClass.'">';
		while($archives->next()){
			$year_tmp = date('Y',$archives->created);
			$mon_tmp = date('m',$archives->created);
			$y=$year; $m=$mon;
			if ($year > $year_tmp || $mon > $mon_tmp) {
				$output .= '</'.$options->listTag.'></div>';
			}
			if ($year != $year_tmp || $mon != $mon_tmp) {
				$year = $year_tmp;
				$mon = $mon_tmp;
				$monthTitle = '';
				if($options->monthTitle){
					$monthTitle = '<'.$options->monthTitleTag.'>'.date($options->monthTitle,$archives->created).'</'.$options->monthTitleTag.'>';
				}
				$output .= '<div class="'.$options->monthClass.'">'.$monthTitle.'<'.$options->listTag.' class="'.$options->listClass.'">'; //输出年份
			}
			$output .= str_replace(array('{permalink}','{title}','{slug}','{cid}','{year}','{month}','{day}','{date}'),
					 array($archives->permalink,$archives->title,$archives->slug,$archives->cid,$year,$mon,date('d',$archives->created),date($options->dateFormat,$archives->created))
					 ,$options->listFormat);
		}
		$output .= '</'.$options->listTag.'></div></div>';
		if(!$options->output){
			return $output;
		}
		echo $output;
		return true;
	}

	/**
     * 生成面包屑导航条
     * 
     * @param Widget_Archive $archive
	 * @param array|string $crumbsOptions 输出配置
     * @return mix
     */
	public static function crumbs($archive, $crumbsOptions=''){
		$options = Typecho_Widget::widget('Widget_Options');
		$crumbsOptions = new Typecho_Config($crumbsOptions);
		$crumbsOptions->setDefault(array(
			'homeTitle'=>'首页',								// 首页名称
			'homeIcon'=>'<i class="icon icon-home"></i>',		// 首页图标
			'indexTitle'=>'最新文章',							// index名称
			'linkFormat'=>'<a href="{url}">{icon} {title}</a>',	// 链接模板
			'textFormat'=>'<span>{icon} {title}</span>',		// 文本模板
			'separator'=>' <i class="icon icon-next"></i> ',	// 分割符
			'defines'=>'',										// 文本替换 默认为 %s
			'output'=>true,										// 是否直接输出
		));
		$crumbs = array();
		$crumbs[] = array('title'=>$crumbsOptions->homeTitle,'url'=>$options->siteUrl,'icon'=>$crumbsOptions->homeIcon);
		
		if($archive->is('index')){
			$crumbs[] = array('title'=>$crumbsOptions->indexTitle,'url'=>'','icon'=>'');
		}elseif($archive->is('post')){
			if(isset($archive->categories[0])){
				self::parseCategoryCrumbs($crumbs, $archive->categories[0]['slug']);
			}
			$crumbs[] = array('title'=>$archive->title,'url'=>'','icon'=>'');
		}elseif($archive->is('category')){
			self::parseCategoryCrumbs($crumbs, $archive->getArchiveSlug());
		}else{
			$define = '%s';
			$archiveType = $archive->getArchiveType();
			if(is_array($crumbsOptions->defines) && isset($crumbsOptions->defines[$archiveType]) && !empty($crumbsOptions->defines[$archiveType])){
				$define = $crumbsOptions->defines[$archiveType];
			}
			$crumbs[] = array('title'=>sprintf($define, $archive->getArchiveTitle()),'url'=>'','icon'=>'');
		}
		$output = array();
		foreach($crumbs as $val){
			$output[] = str_replace(array('{url}','{title}','{icon}'),
					array($val['url'],$val['title'],$val['icon']), (empty($val['url']) ? $crumbsOptions->textFormat : $crumbsOptions->linkFormat));
		}
		$output = implode($crumbsOptions->separator,$output);
		if(!$crumbsOptions->output){
			return $output;
		}
		echo $output;
		return true;
	}

	// 解析分类路径并生成面包屑导航
	protected static function parseCategoryCrumbs(&$crumbs,$slug){
		if($categorie = self::getCategoryBySlug($slug)){
			foreach($categorie['directory'] as $val){
				if($tmpMeta = self::getCategoryBySlug($val)){
					$crumbs[] = array('title'=>$tmpMeta['name'],'url'=>$tmpMeta['permalink'],'icon'=>'');
				}
			}
		}
	}
	
	/**
	 * 显示分类列表
	 * @param string $mids 分类id，支持多个分类
	 * @param string $format 格式化显示模板
	 */
	public static function categories($mids,$format='<li><a href="{permalink}">{name}</a></li>'){
		$categories = self::getAllCategory();
		$tmp = array();
		foreach($categories as $category){
			$tmp[$category['mid']] = $category;
		}
		if(!is_array($mids)){
			$mids = explode(',',$mids);
		}
		$html = '';
		foreach($mids as $mid){
			if(!empty($mid) && isset($tmp[$mid]))
				$html .= str_replace(array('{mid}','{name}','{slug}','{count}','{permalink}','{description}'),
				array($tmp[$mid]['mid'],$tmp[$mid]['name'],$tmp[$mid]['slug'],$tmp[$mid]['count'],$tmp[$mid]['permalink'],$tmp[$mid]['description']),$format);
		}
		echo $html;
	}
	
    /**
	 * 通过分类缩略名获取分类
	 * @param string $slug
	 * @return array
	 */
    public static function getCategoryBySlug($slug){
        $categories = self::getAllCategory();		
        return isset($categories[$slug]) ? $categories[$slug] : array();
    }

	/**
	 * 通过分类id获取分类
	 * @param integer $mid
	 * @return array
	 */
    public static function getCategory($mid){
        $categories = self::getAllCategory();
		foreach($categories as $categorie){
			if($mid == $categorie['mid']){
				return $categorie;
			}
		}	
        return array();
    }
    /**
	 * 获取分类列表
	 * 
	 * @return array
	 */
	public static function getAllCategory(){
		static $categories;
		if(is_null($categories)){
			$obj = Typecho_Widget::widget('Widget_Metas_Category_List');
			while($obj->next()){
				$categories[$obj->slug] = array(
					'mid'=>$obj->mid,
					'slug'=>$obj->slug,
					'name'=>$obj->name,
					'permalink'=>$obj->permalink,
					'directory'=>$obj->directory,
					'count'=>$obj->count,
					'description'=>$obj->description
				);
			}
		}
		return $categories;
	}

	// 新建字段
    public static function installDb(){
		$installDb = Typecho_Db::get();
		$type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
		if('mysql' != strtolower($type)){
			return _t('仅支持Mysql数据库自动创建字段;插件成功启用，使用其他类型数据库请手动创建字段');
		}
		
		$addFields = array(
			'viewsNum'=>"ADD COLUMN `viewsNum` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数' AFTER `commentsNum`;",
			'likesNum'=>"ADD COLUMN `likesNum` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数' AFTER `viewsNum`;",
			'thumbnail'=>"ADD COLUMN `thumbnail` varchar(200) NOT NULL DEFAULT '' COMMENT '缩略图' AFTER `likesNum`;"
		);

		$table = $installDb->getPrefix().'contents';
		$fields = $installDb->fetchAll('desc '.$table);
		foreach($fields as $field){
			if(isset($addFields[$field['Field']])){
				unset($addFields[$field['Field']]);
			}
		}
		if(!empty($addFields)){
			foreach($addFields as $addField=>$sql){
				$installDb->query('ALTER TABLE '.$table.' '.$sql);
			}
			return _t('字段成功创建;插件成功启用');
		}else{
			return _t('字段已存在;插件成功启用');
		}
	}

    // 后台缩略图选项
    public static function addThumbnailOption($contents){
        $html = '<section class="typecho-post-option">
            <label for="thumbnail" class="typecho-label">缩略图</label>
            <p><input id="thumbnail" name="thumbnail" type="text" value="'.$contents->thumbnail.'" class="w-100 text" /></p>
        </section>
        <section class="typecho-post-option">
            <label for="thumbnail-preview" class="typecho-label">缩略图预览</label>
            <p class="thumbnail-preview"><img src="'.$contents->thumbnail.'" id="thumbnail-preview"></p>
        </section>';
        echo $html;
    }

    // 后台头部
    public static function addAdminHeader($header){
        list($prefixVersion, $suffixVersion) = explode('/', Typecho_Common::VERSION);
        $cssUrl = Typecho_Common::url('Textends/static/extends.css?'.$suffixVersion,Typecho_Widget::widget('Widget_Options')->pluginUrl);
		$jsUrl = Typecho_Common::url('Textends/static/extends.js?'.$suffixVersion,Typecho_Widget::widget('Widget_Options')->pluginUrl);
		$header .=  '<link rel="stylesheet" href="'.$cssUrl.'"><script src="'.$jsUrl.'"></script>';
		return $header;
	}

}