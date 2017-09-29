<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 获取评论列表
 * @author 绛木子 <master@lixianhua.com>
 */
class Textends_Widget_Comments extends Widget_Abstract_Comments{

    /**
     * 分页计算对象
     *
     * @access private
     * @var Typecho_Db_Query
     */
    private $_countSql;

	/**
     * 所有评论个数
     *
     * @access private
     * @var integer
     */
    private $_total = false;

    /**
     * 所有评论页数
     *
     * @access private
     * @var integer
     */
    private $_totalPage = false;

	/**
     * 执行函数
     *
     * @access public
     * @return void
     */
	public function execute()
    {
        $this->parameter->setDefault(
            array(
                'cid'=>'',
                'uid'=>'',
                'limit'=>$this->options->commentsListSize,		// 数据条数
                'page' => 1,
                'sort' => 'coid',
                'desc' => true,
                'status' => 'approved',
            ));
        
        $select  = $this->select()->where('table.comments.type = ?', 'comment')
        ->order('table.comments.'.$this->parameter->sort, $this->parameter->desc ? Typecho_Db::SORT_DESC: Typecho_Db::SORT_ASC);

        if ($this->parameter->uid) {
            $select->where('table.comments.authorId = ?', $this->parameter->uid);
        }
        if ($this->parameter->cid) {
            $select->where('table.comments.cid = ?', $this->parameter->cid);
        }
        if ($this->parameter->status) {
            $select->where('table.comments.status = ?', $this->parameter->status);
        }
		
		$this->_countSql = clone $select;
        if($this->parameter->page > 1){
            $select->page($this->parameter->page, $this->parameter->limit);
        }else{
            $select->limit($this->parameter->limit);
        }
        $this->db->fetchAll($select, array($this, 'push'));
    }

    public function getTotal(){
        if (false === $this->_total) {
            $this->_total = $this->size($this->_countSql);
        }

        return $this->_total;
    }

    public function getTotalPage(){
        if (false === $this->_totalPage) {
            $this->_totalPage = ceil($this->getTotal()/$this->parameter->limit);
        }

        return $this->_totalPage;
    }

}