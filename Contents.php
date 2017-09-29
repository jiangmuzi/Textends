<?php
/**
 * 内容
 * @author 绛木子 <master@lixianhua.com>
 */
class Textends_Contents extends Typecho_Widget{

    // 内容处理
    public static function contentEx($content,$obj){
        self::contentUseCdn($content);
        return $content;
        // 短代码支持
        // return Textends_Shortcodes::execute($content);
    }

    // 处理内容字段
    public static function filter($value, $archive){
        $statusWords = array(
            'publish' => _t('公开'),
            'hidden' => _t('隐藏'),
            'password' => _t('密码保护'),
            'private' => _t('私密'),
            'waiting' => _t('待审核'),
        );
        $value['statusWord'] = isset($statusWords[$value['status']]) ? $statusWords[$value['status']] : $value['status'];
        $hasExtends = true;
        if(!isset($value['viewsNum'])){
            $db = Typecho_Db::get();
            $extends = $db->fetchRow($db->select('*')
                ->where('cid = ?', $value['cid'])
                ->from('table.contents')->limit(1));
            
            if($extends){
                foreach($extends as $field=>$val){
                    if(!isset($value[$field])){
                        $value[$field] = $val;
                    }
                }
            }
        }
		if(!empty($value['thumbnail']) && false === strpos($value['thumbnail'],'http')){
            $value['thumbnail'] = rtrim(Helper::options()->siteUrl,'/') . '/' . $value['thumbnail'];
        }
        
        return $value;
    }
 
    // 使用cdn地址替换静态资源地址
    // 支持图片的赖加载，需在主题中添加设置
    public static function contentUseCdn(&$content){
        $siteUrl = Helper::options()->siteUrl;
        $lazyImg = Helper::options()->lazyImg;
        $pattern="/<[img|IMG].*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/";
        preg_match_all($pattern,$content,$match);
        if(count($match[1])>0){
            $div = $match[0];
            $imgs = $match[1];
            //$alt = $match[2];
            foreach($imgs as $k=>$v){
                $v = Textends_Plugin::cdnReplace($v);
                if(empty($lazyImg)){
                    $img = '<img class="post-content-img" src="'.$v.'"/>';
                }else{
                    $img = '<img class="post-content-img lazy" data-original="'.$v.'"/>';
                }
                $content = str_replace($div[$k], $img, $content);
            }
        }
    }

    // 发布、编辑内容时，更新扩展字段
	public static function addContentsExtends($contents, $edit){
		if(!in_array($contents['type'],array('post','page'))){
			return;
		}
        $thumbnail = $edit->request->get('thumbnail','');
        if(!empty($thumbnail)){
            $thumbnail = self::parseThumbnailLocalPath($thumbnail,Helper::options()->siteUrl);
        }
        $data = array('thumbnail'=>$thumbnail);
        $db = Typecho_Db::get();
        $db->query($db->update('table.contents')->where('table.contents.cid = ?',$edit->cid)->rows($data));
	}

	//解析缩略图本地地址
    public static function parseThumbnailLocalPath($url,$siteUrl){
        if(false === strpos($url,$siteUrl)){
            return $url;
        }
        $url = ltrim(substr($url,strlen($siteUrl)),'/');
        return $url;
    }

	// 浏览计数器
	public static function viewCounter($archive){
		if($archive->is('single') && $archive->cid){
            $cid = $archive->cid;
			
            $views = Typecho_Cookie::get('__te_pvs');
            if(empty($views)){
                $views = array();
            }else{
                $views = explode(',', $views);
            }
			
            if(!in_array($cid,$views)){
                $db = Typecho_Db::get();
                $db->query($db->update('table.contents')->expression('viewsNum', 'viewsNum + 1')->where('cid = ?', $cid));
                array_push($views, $cid);
                $views = implode(',', $views);
                Typecho_Cookie::set('__te_pvs', $views); //记录到cookie
            }
        }
	}

	// like计数器
	public static function likeCounter($archive){
		if($archive->is('single') && $archive->cid){
            $cid = $archive->cid;
            $likes = Typecho_Cookie::get('__te_pls');
            if(empty($likes)){
                $likes = array();
            }else{
                $likes = explode(',', $likes);
            }
			
            if(!in_array($cid,$likes)){
                $archive->likesNum += 1;
                $db = Typecho_Db::get();
                $db->query($db->update('table.contents')->expression('likesNum', 'likesNum + 1')->where('cid = ?', $cid));
                array_push($likes, $cid);
                $likes = implode(',', $likes);
                Typecho_Cookie::set('__te_pls', $likes); //记录到cookie
                return $archive->likesNum;
            }
            return -1;
        }
        return 0;
	}

    // 清除默认的查询字段，使之查询所有的字段，部分位置不兼容此方式(继承自Widget_Archive才支持)
	public static function select($archive = null){
		$_feed = $archive ? $archive->getFeed() : false;

		$select = Typecho_Db::get()->select('table.contents.*')->from('table.contents');

        $user = Typecho_Widget::widget('Widget_User');
        if ($archive && ('post' == Typecho_Router::$current || 'page' == Typecho_Router::$current) ) {
            if ($user->hasLogin()) {
                $select->where('table.contents.status = ? OR table.contents.status = ? OR
                        (table.contents.status = ? AND table.contents.authorId = ?)',
                        'publish', 'hidden', 'private', $user->uid);
            } else {
                $select->where('table.contents.status = ? OR table.contents.status = ?', 'publish', 'hidden');
            }
        } else {
            if ($user->hasLogin()) {
                $select->where('table.contents.status = ? OR
                        (table.contents.status = ? AND table.contents.authorId = ?)', 'publish', 'private', $user->uid);
            } else {
                $select->where('table.contents.status = ?', 'publish');
            }
        }
        $select->where('table.contents.created < ?', Helper::options()->gmtTime);

		if ($_feed) {
            // 对feed输出加入限制条件
            return $select->where('table.contents.allowFeed = ?', 1)
            ->where('table.contents.password IS NULL');
        } else {
            return $select;
        }
	}
}