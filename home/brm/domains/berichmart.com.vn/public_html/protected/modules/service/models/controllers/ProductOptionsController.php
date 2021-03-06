<?php
class ProductOptionsController extends Controller
{
    
    public function actionIndex()
	{    // mac dinh chay giao dien test
            $this->render('index');
	}
    public function actionGetProductOptions(){
        $sql='select * from product_options'; 
        $condition ='';
        if(isset($_GET['id']) && intval($_GET['id'])){
           $condition .= (empty($condition))?' where ':' and ';
           $condition .=' id='.$_GET['id'] ;
        }
        if(isset($_GET['group_product_id']) && intval($_GET['group_product_id'])) {
            $group_product_id = intval($_GET['group_product_id']);            
            $condition .= (empty($condition))?' where ':' and ';
           $condition .=' group_product_id='.$group_product_id ;
        } 
        $number_of_posts = isset($_GET['num']) ? intval($_GET['num']) : ''; //All is the default
        $sql .= $condition;
        $sql .=(!empty($number_of_posts))?' limit '.$number_of_posts:'';
        // echo $sql; die;  
        $productOptions = Yii::app()->db->createCommand($sql)->queryAll();  
        foreach($productOptions as $pro){
            $data[]['productOption'] = $pro;
        }
        header('Content-type: application/json');
        if(isset($data))
            echo json_encode(array('posts'=>$data));
        else echo false;
    }
    
    public function actionInsertProductOption(){
        //$json=$_GET ['json'];
        $json = file_get_contents('php://input');
        $obj = json_decode($json);        
        $data = parseObjectToArray($obj);
        $productOption = new ProductOption();
        if($data['type']=='select')
                $data['value']= serialize(explode('#', $data['value']));
        $productOption->attributes=$data;
        if($productOption->save())
            echo true;
        else
            echo false;
    }
    
    public function actionUpdateProductOption(){
        //$json=$_GET ['json'];
        $json = file_get_contents('php://input');
        $obj = json_decode($json);        
        $data = parseObjectToArray($obj);
        if(isset($_GET['id'])){
            $productOption =  ProductOption::model()->findByPk($_GET['id']);
            $productOption->attributes=$data;
            if($productOption->save())
                echo true;
            else
                echo false;
        }
    }
    
    public function actionDeleteProductOption(){
        $id =(isset($_GET['id']))?$_GET['id']:'';
        if(ProductOption::model()->findByPk($id)->delete())
            echo true;
        else 
            echo false;
    }
    
    public function actionTest(){ 
        // demo post du lieu json de insert
        $data = array('id'=>128,'name'=>'nguyen van cong','address'=>'tu liem ha noi');
        $data= json_encode($data); // echo 'http://'.$_SERVER['SERVER_NAME'].getURL().'service/productOptions/insertproductOption';        
        $result = file_post_contents('http://'.$_SERVER['SERVER_NAME'].getURL().'service/productOptions/insertproductOption', $data);
        if(empty($result))
            echo 'khong co du lieu';
        else {
           // header('Content-type: application/json');
            echo $result;
            $result = json_decode($result);
            pr(parseObjectToArray($result));
            }
    }
        
}