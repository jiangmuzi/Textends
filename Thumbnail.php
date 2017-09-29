<?php
/**
 * 文章缩略图
 * @author 绛木子 <master@lixianhua.com>
 */
class Textends_Thumbnail extends Typecho_Widget{

    /**
     * 全局选项
     *
     * @access protected
     * @var Widget_Options
     */
    protected $options;

    /**
     * 缩略图参数
     *
     * @access protected
     * @var array
     */
    protected $params;

    /**
     * 错误信息
     *
     * @access protected
     * @var string
     */
    protected $error;

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        /** 初始化常用组件 */
        $this->options = $this->widget('Widget_Options');
    }

    public function execute(){
        $this->parseParams();
		$this->parseRealPath();
    }

	public function render(){
        if($this->error){
            exit($this->error);
        }
		
        require_once ('Libs/Image.php');
        $image = new Image();
		if(isset($this->params['realPath'])){
			$image->open($this->params['realPath']);
		}elseif(isset($this->params['string'])){
			$image->createFromString($this->params['string']);
		}else{
            exit('没有图像显示');
        }
		
		$type = $image->type();
		
		$image->thumb($this->params['width'], $this->params['height'],$this->params['mode']);
		
		header('Content-Type:image/'.$type.';');
        header('Cache-Control:private');

		//输出图像
		if('jpeg' == $type || 'jpg' == $type){
			// 采用jpeg方式输出
			imagejpeg($image->showImg());
		}elseif('gif' == $type){
			imagegif($image->showImg());
		}else{
			$fun  =   'image'.$type;
			$fun($image->showImg());
		}
    }
	
	// 解析缩略图原图地址
	private function parseRealPath(){

        $realPath = __TYPECHO_ROOT_DIR__ . '/usr/uploads/' . $this->params['path'];
		// 本地图片缩略图
		if(is_file($realPath)){
			$this->params['realPath'] = $realPath;
			return;
		}
		$realPath = __TYPECHO_ROOT_DIR__ . '/' . $this->params['path'];
		if(is_file($realPath)){
			$this->params['realPath'] = $realPath;
			return;
		}
		// 远程图片或图片地址有误
		$string = file_get_contents($this->params['path']);
		
		if($string){
			$this->params['string'] = $string;
			return;
		}
		$this->error = '图片信息错误';
	}
	
	// 解析缩略图参数
    private function parseParams(){
        $ident = $this->options->thumbnailIdent;
        $tmp = explode($ident,$this->request->params);
		$paramString = isset($tmp[1]) ? $tmp[1] : '';
        
		// 允许的参数列表
        $allowParams = array(
            'm'=>array('name'=>'mode','default'=>0,'min'=>0,'max'=>5),
            'w'=>array('name'=>'width','default'=>200,'min'=>1,'max'=>0),
            'h'=>array('name'=>'height','default'=>100,'min'=>1,'max'=>0),
            'q'=>array('name'=>'quality','default'=>75,'min'=>0,'max'=>100)
        );
		
        $this->params = array();
        foreach($allowParams as $key=>$val){
            $regx = "/{$key}\/(\d+)/";
			$num = null;
			if(!empty($paramString)){
				preg_match_all($regx, $paramString, $matches);
				if($matches[1]){
					$num = $matches[1][0];
				}
			}
            // 参数是否在允许范围
            if(0 == $val['max'] && $num != null){
                $val['max'] = $num;
            }
			if($num != null && $num >= $val['min'] && $num <= $val['max']){
				$this->params[$val['name']] = $num;
			}else{
				$this->params[$val['name']] = $val['default'];
			}
            
        }
        $this->params['path'] = $tmp[0];
		
    }

}