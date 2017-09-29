<?php
/**
 * Action
 * @author 绛木子 <master@lixianhua.com>
 */
class Textends_Action extends Typecho_Widget implements Widget_Interface_Do{
    public function doLike(){
		$cid = $this->request->filter('int')->cid;
        if(!$cid){
            $this->response->throwJson(array('status'=>0,'msg'=>'请选择点赞的文章!'));
        }
		$this->widget('Widget_Archive@single_'.$cid,'type=single&cid='.$cid)->to($archive);

        $result = Textends_Contents::likeCounter($archive);

		if(0 === $result){
			$this->response->throwJson(array('status'=>0,'msg'=>'请选择点赞的文章!'));
		}elseif( -1 === $result){
            $this->response->throwJson(array('status'=>0,'msg'=>'您已经点赞过了!'));
        }else{
            $this->response->throwJson(array('status'=>1,'msg'=>'您已成功点赞!','likesNum'=>$result));
        }
	}

    public function action(){
        $this->widget('Widget_Security')->protect();
        $this->on($this->request->is('do=like'))->doLike();
    }
}