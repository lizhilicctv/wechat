<?php
/**
 * 分页类
 * 作者 : 深海 5213606@qq.com
 * 官网 : http://www.hcoder.net/hcwt
 */
class hcPage {
	public $pagerType;	
	public $pagerUrlName = 'page';
	public $totalRows;
	public $eachPage;
	public $maxPage;
	public $limit;
	public $currentUrl;
	public $lang = array('firstPage' => '|&lt;', 'prePage' => '&lt;&lt;', 'nextPage'  => '&gt;&gt;', 'lastPage'  => '&gt;|');
	public function __construct($totalRows, $eachPage = 10, $pagerType = 1){
		$this->totalRows  = $totalRows;
		$this->eachPage   = $eachPage;
		$this->pagerType  = $pagerType;
		$totalRows < 1 ? $this->maxPage = 1 : $this->maxPage = ceil($totalRows/$eachPage);
		empty($_GET[$this->pagerUrlName]) ? $_GET[$this->pagerUrlName] = 1 : $_GET[$this->pagerUrlName] = intval($_GET[$this->pagerUrlName]);
		if($_GET[$this->pagerUrlName] < 1){$_GET[$this->pagerUrlName] = 1;}
		if($_GET[$this->pagerUrlName] > $this->maxPage){$_GET[$this->pagerUrlName] = $this->maxPage;}
		$this->limit = ' limit '.(($_GET[$this->pagerUrlName]-1) * $eachPage).','.$eachPage;
		$this->currentUrl();
	}
	private function currentUrl(){
		if(!in_array($this->pagerType, array(1,2,3))){throw new witException('分页类型设置错误','请将分页类型设置为 1,2,3 中的一个值');}
		switch ($this->pagerType){
			case 1:
				$this->currentUrl = $_SERVER['SCRIPT_NAME'].'?';
				foreach ($_GET as $k => $v){if($k != $this->pagerUrlName && $k != 'wits'){$this->currentUrl .= $k.'='.$v.'&';}}
			break;
			case 2:
				$this->currentUrl = $_SERVER['SCRIPT_NAME'];
				foreach ($_GET as $k => $v){
					if(!is_array($v)){if($k != 'wits' && $k != $this->pagerUrlName){
						if($k == 'c' || $k == 'm'){$this->currentUrl .= '/'.$v;}else{$this->currentUrl .= '/'.$k.'/'.$v;}}
					}
				}
			break;
			case 3:
				$arrCurrentUrl    = explode('/', $_SERVER['SCRIPT_NAME']);
				array_pop($arrCurrentUrl);
				$this->currentUrl = implode('/', $arrCurrentUrl);
				foreach ($_GET as $k => $v)
				{
					if(!is_array($v)){if($k != $this->pagerUrlName && $k != 'wits'){
						if($k == 'c' || $k == 'm'){$this->currentUrl .= '/'.$v;}else{$this->currentUrl .= '/'.$k.'/'.$v;}}
					}
				}
			break;
		}
	}

	public function firstPage(){
		if($this->pagerType == 1){return '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'=1">'.$this->lang['firstPage'].'</a></div>';}
		return '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/1">'.$this->lang['firstPage'].'</a></div>';
	}
	
	public function prePage(){
		if($this->pagerType == 1){return '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'='.($_GET[$this->pagerUrlName]-1).'">'.$this->lang['prePage'].'</a></div>';}
		return '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/'.($_GET[$this->pagerUrlName]-1).'">'.$this->lang['prePage'].'</a></div>';
	}
	
	public function nextPage(){
		if($this->pagerType == 1){return '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'='.($_GET[$this->pagerUrlName]+1).'">'.$this->lang['nextPage'].'</a></div>';}
		return '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/'.($_GET[$this->pagerUrlName]+1).'">'.$this->lang['nextPage'].'</a></div>';
	}
	
	public function lastPage(){
		if($this->pagerType == 1){return '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'='.$this->maxPage.'">'.$this->lang['lastPage'].'</a></div>';}
		return '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/'.$this->maxPage.'">'.$this->lang['lastPage'].'</a></div>';
	}
	
	public function listPage(){
		if($_GET[$this->pagerUrlName] <= 3){$start = 1; $end = 5;}else{$start = $_GET[$this->pagerUrlName] - 2; $end = $_GET[$this->pagerUrlName] + 2;}
		if($end > $this->maxPage){$end = $this->maxPage;}
		if($end - $start < 4){$start = $end - 4;}
		if($start < 1){$start = 1;}
		$return = '';
		for($i=$start; $i<=$end; $i++){
			if($this->pagerType == 1){
				$i == $_GET[$this->pagerUrlName] ? $return .= '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'='.$i.'" class="pagerC">'.$i.'</a></div>' : $return .= '<div><a href="'.$this->currentUrl.$this->pagerUrlName.'='.$i.'">'.$i.'</a></div>';
			}else{
				$i == $_GET[$this->pagerUrlName] ? $return .= '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/'.$i.'" class="pagerC">'.$i.'</a></div>' : $return .= '<div><a href="'.$this->currentUrl.'/'.$this->pagerUrlName.'/'.$i.'">'.$i.'</a></div>'; 
			}
		}
		return $return;
	}
	
	public function skipPage(){
		$this->pagerType == 1 ? $str = '<select onchange="location.href=\''.$this->currentUrl.$this->pagerUrlName.'=\'+this.value;">' : $str = '<select onchange="location.href=\''.$this->currentUrl.'/'.$this->pagerUrlName.'/\'+this.value;">';
		for($i = 1; $i <= $this->maxPage; $i++){
			$i == $_GET[$this->pagerUrlName] ? $str .= '<option value="'.$i.'" selected="selected">'.$i.'</option>' : $str .= '<option value="'.$i.'">'.$i.'</option>';
		}
		$str .= '</select>';
		return $str;
	}
}