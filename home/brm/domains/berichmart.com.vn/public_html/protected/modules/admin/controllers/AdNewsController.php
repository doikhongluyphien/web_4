<?php

class AdNewsController extends Controller{
    public $layout = 'default';
    
    public function actionIndex(){
        checkLogin($this);
        $criteria = new CDbCriteria(); // tao dieu kien
        $criteria->order="id desc";
        $count = AdNews::model()->count($criteria); // dem so ban ghi theo dieu kien
        $pages=new CPagination($count); // tao phan trang chua tong so ban ghi
        $pages->pageSize=12; // so ban ghi tren 1 trang
        $pages->applyLimit($criteria); //dieu kien phan trang
        $data = AdNews::model()->findAll($criteria); // mang chua ds cac ban ghi theo dieu kien
        $this->render("index",array('data'=>$data,'pages'=>$pages)); // gui du lieu ra view
    }
     public function actionView($id=null) {
        checkLogin($this);
        $AdNews = AdNews::model()->findByPk($id); 
        $this->render("view", array ('AdNews'=>$AdNews));
    }
    public function actionAdd(){
        checkLogin($this);
        $AdNews = new AdNews();      
        $cities = City::model()->findAll("status = 1 order by stt asc");
        $arr_city = CHtml::listData($cities, 'id', 'name');
        // Uncomment the following line if AJAX validation is needed
	$this->performAjaxValidation($AdNews);
        if(isset($_POST['AdNews'])){ //pr($_POST); die;            
            $data = $_POST['AdNews']; //pr($data); die;
            $data['status']=1;
            $data['member_id']= Yii::app()->session['user']['id'];
            $data['alias']= char($data['title']);
            date_default_timezone_set('asia/saigon');
            $data['created']=date('Y-m-d H:i:s');
            $data['modified']=date('Y-m-d H:i:s'); 
            $AdNews->attributes = $data;
            pr($AdNews->attributes);
            if($AdNews->save())// chi luu khi cac truong du lieu khong null deu co gia trị
            $this->redirect(array('index'));
        }
       $listcat = Category::model()->generateTree('parent_id is null and id<>257','--');
        $this->render("add",array('AdNews'=>$AdNews,'listcat'=>$listcat,'cities'=>$arr_city));
    }
    public function actionEdit($id = null){
        checkLogin($this);
        $AdNews = AdNews::model()->findByPk($id);  
        $cities = City::model()->findAll("status = 1 order by stt asc");
        $arr_city = CHtml::listData($cities, 'id', 'name');
        $this->performAjaxValidation($AdNews);
        if(isset($_POST['AdNews'])){ //pr($_POST); die;
            $data = $_POST['AdNews'];
            $data['member_id']= Yii::app()->session['user']['id'];
            date_default_timezone_set('asia/saigon');
            $data['modified']=date('Y-m-d H:i:s'); 
            $AdNews->attributes = $data;
            if($AdNews->save())// chi luu khi cac truong du lieu khong null deu co gia trị
            $this->redirect(array('index'));
        }     
        $listcat = Category::model()->generateTree('parent_id is null and id<>257','--');
        $this->render("edit",array('AdNews'=>$AdNews,'listcat'=>$listcat,'cities'=>$arr_city));
    }
    public function actionDelete($id=null){
        checkLogin($this);
         AdNews::model()->findByPk($id)->delete();
         $this->redirect(array('index'));
    }
    public function actionUpdateStatus($id){
        checkLogin($this);
        $AdNews = AdNews::model()->findByPk($id);
        $AdNews->status = ($AdNews->status==0)?1:0;
        $AdNews->save();
        $this->redirect(array('index'));
    }
    
    protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='frm')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
        
        public function beforeAction($action) { 
            $checklogin = checkLogin($this);
            if($checklogin==true){
                $user_id=Yii::app()->session['user']['id'];
                $phanquyen = Yii::app()->db->createCommand("select * from phan_quyen where user_id='".$user_id."'")->queryRow();
                if(!empty($phanquyen)){
                    $pq = unserialize($phanquyen['quyen']);
                    $controller = Yii::app()->controller->id;
                    $action = Yii::app()->controller->action->id; //echo $pq[$controller][$action]; die;
                    if(isset($pq[$controller][$action])&&$pq[$controller][$action]==0)
                        $this->redirect(getURL().'site/message/83');
                    else 
                        return TRUE;
                }
                else  return FALSE;
            }
            else  return FALSE;
        }
}
?>
