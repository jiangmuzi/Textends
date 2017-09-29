<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 获取文章列表
 * @author 绛木子 <master@lixianhua.com>
 */
class Textends_Widget_Contents extends Widget_Abstract_Contents{
	
	/**
     * 获取查询对象
     *
     * @access public
     * @return Typecho_Db_Query
     */
    public function select()
    {
        return Textends_Contents::select();
    }
	
    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        $this->parameter->setDefault(array(
            'type'=>'post',
            'orderby'=>'created',
            'sort'=>Typecho_Db::SORT_DESC,
            'cid'=>'',
            'uid'=>'',
            'mid'=>'',
			'children'=>0,
            'day'=>0,
			'limit'=>10,
            'ignoreEmptyThumbnail'=>false
        ));

        $select = $this->select()
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $this->options->gmtTime)
        ->where('table.contents.type = ?', $this->parameter->type);

        if($this->parameter->ignoreEmptyThumbnail){
            $select->where('table.contents.thumbnail <> ?','');
        }

        // 用户
        if($this->parameter->uid != ''){
            if(!is_array($this->parameter->uid)){
                if(is_numeric($this->parameter->uid)){
                    $select->where('table.contents.authorId = ?', $this->parameter->uid);
                }else{
                    $this->parameter->uid = explode(',',trim($this->parameter->uid));
                    $select->where('table.contents.authorId IN ?', $this->parameter->uid);
                }
                
            }else{
                $select->where('table.contents.authorId IN ?', $this->parameter->uid);
            }
        }
        // 内容id
        if($this->parameter->cid != ''){
            if(!is_array($this->parameter->cid)){
                $this->parameter->cid = explode(',',trim($this->parameter->cid));
            }
            $select->where('table.contents.cid IN ?', $this->parameter->cid);
        }
    
        // 分类id
        if($this->parameter->mid != ''){
            if(!is_array($this->parameter->mid)){
                $mids = explode(',',trim($this->parameter->mid));
            }else{
                $mids = $this->parameter->mid;
            }
            
            if($this->parameter->children){
                foreach($mids as $mid){
                    $childrenMids = $this->getMateChildrens($mid);
                    if(!empty($childrenMids)){
                        $mids = array_merge($mids,$childrenMids);
                    }
                }
            }

            $select->join('table.relationships', 'table.contents.cid = table.relationships.cid')
            ->where('table.relationships.mid IN ?', $mids)
            ->group('table.contents.cid');
        }

        // 时间
        if($this->parameter->day > 0 ){
            $select->where('table.contents.created > ?', $this->options->gmtTime - ($this->parameter->day*86400));
        }

        // 排序
        $this->parameter->sort = strtoupper($this->parameter->sort);
        $this->parameter->sort = ($this->parameter->sort == Typecho_Db::SORT_DESC || $this->parameter->sort == Typecho_Db::SORT_ASC) ? $this->parameter->sort : Typecho_Db::SORT_DESC;
        
        if(is_array($this->parameter->orderby)){
            foreach($this->parameter->orderby as $order=>$sort){
                if(is_numeric($order)){
                    $order = $sort;
                    $sort = $this->parameter->sort;
                }else{
                    $sort = strtoupper($sort);
                    $sort = ($sort == Typecho_Db::SORT_DESC || $sort == Typecho_Db::SORT_ASC) ? $sort : Typecho_Db::SORT_DESC;
                }
                $select->order('table.contents.'.$order,$sort);
            }
            
        }else{
            $this->parameter->orderby = 'table.contents.'.$this->parameter->orderby;
            $select->order($this->parameter->orderby, $this->parameter->sort);
        }
        if($this->parameter->limit){
            $select->limit($this->parameter->limit);
        }

        $contents = $this->db->fetchAll($select,array($this,'push'));
    }

    public function filter($value){
        $value = parent::filter($value);
        $value = Textends_Contents::filter($value,$this);
        return $value;
    }
	
	private function getMateChildrens($mid){
        $metas = $this->widget('Widget_Metas_Category_List')->getAllChildren($mid);
        if(empty($metas)){
            return null;
        }
        return $metas;
    }
}