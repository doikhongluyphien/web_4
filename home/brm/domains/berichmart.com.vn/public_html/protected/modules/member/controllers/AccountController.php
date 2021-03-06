<?php
// Yii::import("application.library.Nested_Set");
class AccountController extends Controller
{
    public $layout='account';
    public $member;
    public $sale;
    
    public function actions()
        {
            return array(
            'captcha'=>array('class'=>'CCaptchaAction',
                            'backColor'=>0xFFFFFF,
                            'foreColor'=>0x000,
                            'height'=>'50',
                            'width'=>'120'
                )
            );
        }
        
    public function actionIndex(){       
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $card = CardAccount::model()->find('member_id="'.$member->id.'"');
        if(empty($card))
            $this->redirect('account/registerCard');
        else
            $this->redirect('account/infoAccount');
    }
    
    // doi mat khau tai khoan
    public function actionChangePassword(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $this->performAjaxValidation($member);        
        if(isset($_POST['Member'])){
            $data=$_POST['Member'];
            $data['password'] = addCode($data['newpass'], 3);
            $member->attributes=$data; 
            if($member->save())
                $this->redirect(array('/member/default/rose/'.$member->id));
        }
        $this->render('changepassword',  array('member'=>$member));
    }
    
    //check pass
    public function actionCheckPass(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $pass = $_POST['pass'];
        if($member->password ==  addCode($pass, 3))
            echo 'yes';
        else
            echo 'no';
    }

    //doi mat khau the
    public function actionChangePasswordCard(){ 
        $cardaccount = CardAccount::model()->find('member_id='.Yii::app()->session['member']['id']);
        $this->performAjaxValidation($cardaccount);   
        if(isset($_POST['CardAccount'])){
            $data =$_POST['CardAccount'];
            $data['password_card'] = addCode($data['newpass'], 3);
            $cardaccount->attributes = $data;
            if($cardaccount->save())
                $this->redirect('infoAccount');
        }         
        $this->render('changepasswordcard', array('cardaccount'=>$cardaccount));
    }
    
    // quen mat khau the
    public function actionForgetPass(){
        if(isset($_POST['security_code'])){
            $captcha=Yii::app()->controller->createAction("captcha");
            $code = $captcha->verifyCode;
            if(trim($_POST['security_code'])==$code){
                echo 'ma bao mat chinh xac'; die;
            }
        }
        $this->render('forget_pass');
    }

    // danh sach tai khoan
    public function actionListAccount() {
        $cardaccount = CardAccount::model()->find('member_id='.Yii::app()->session['member']['id']);      
        $this->render('list_account',array('cardaccount'=>$cardaccount));
    }
    
    // thong tin tai khoan
    public function actionInfoAccount(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $this->render('info_account',array('member'=>$member));
    }
    
    // chi tiet giao dich
    public function actionDetailTransaction(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']); 
        $from = (isset($_POST['d_from']))?trim($_POST['d_from']):'';
        $to = (isset($_POST['d_to']))?trim($_POST['d_to']):'';   
        
        if(!empty($from)&&!empty($to)){
                $from = str_replace('/', '-', $from);
                $to = str_replace('/', '-', $to);   
         } else {
             $from = '1-'.date('m-Y');
             $to = date('d-m-Y');
         }       
        $transfers = Transfer::model()->findAll('(account_send = ? or account_get=?) and year(created)>= ? and year(created)<=? and month(created)>= ? and month(created)<=? and dayofmonth(created)>= ? and dayofmonth(created)<=?',array($member->CardAccount['numberaccount'],$member->CardAccount['numberaccount'],date('Y',  strtotime($from)),date('Y',  strtotime($to)),date('m',  strtotime($from)),date('m',  strtotime($to)),date('d',  strtotime($from)),date('d',  strtotime($to))));         
        $this->render('detai_transaction',array('transfers'=>$transfers,'member'=>$member,'from'=>str_replace('-', '/', $from),'to'=>str_replace('-', '/', $to)));
    }
    
    //thong tin the
    public function actionInfoCard(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $this->render('info_card',array('member'=>$member));
    }
    
    //dang ky dich vu
    public function actionRegisterService(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $account=  CardAccountNoCheck::model()->find('member_id='.Yii::app()->session['member']['id']);
        if(isset($_POST['maxtransfer'])){           
            $account->max_transfer = $_POST['maxtransfer'];
            if($account->save())
                $this->redirect (getURL ().'site/message/12');
        }
        $regulation = Regulation::model()->findByPk(1);
        $this->render('register_service',array('account'=>$account,'member'=>$member,'regulation'=>$regulation));
    }
    
    // thay doi phone
    public function actionChangeMobile(){
        $cardaccount = CardAccount::model()->find('member_id='.Yii::app()->session['member']['id']);
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $this->performAjaxValidation($cardaccount);   
        if(isset($_POST['data'])){ 
            if(!empty($_POST['data']['mobile']))
                $mobile=$_POST['data']['mobile'];
            else 
                $mobile=$_POST['data']['combomobile'];
            $session = getSession();
            $code = rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9);
            $session['change_mobile']= array('mobile'=>$mobile,'code'=>$code);
            
            //sen sms
            require_once 'class/nusoap_sms/nusoap.php';            
            $wsdl = 'http://sms-gw1.apectech.vn:8081/axis/services/Mt_receicer?wsdl';
            $client = new nusoap_client($wsdl, true);
            $msg_id = ceil(rand(0, 1000));
            $msisdn= $cardaccount->mobile;
            $message = '[HoanGia]'.date('H:i:s d-m-Y').'Ma xac nhan thay doi so dien thoai tai berichmart.com.vn la :'.$code;
            $brandname = 'hoanggia';
            $username = 'hoanggiacorp';
            $password = 'reh7$8eh^e@92ye';
            $sharekey = 'FJU445O9G94NFHH30CJ6H';
            $hashkey = md5("{$msg_id}{$msisdn}{$message}{$brandname}{$username}{$password}{$sharekey}");
            $params =array('msg_id'=>$msg_id,'msisdn'=>$msisdn,'message'=>$message,'brandname'=>$brandname,'username'=>$username,'password'=>$password,'hashkey'=>$hashkey);
            $result = $client->call('sendTextMessage', $params);
           // echo $result; die;
        //  echo "<p>Apectech Result:". var_dump($result)."</p>";
        // end send sms
            
            $this->redirect('confirmChangeMobile');
        }
        $this->render('change_mobile', array('cardaccount'=>$cardaccount,'member'=>$member));
    }
    
    // xac nhan thong tin khi doi mat khau
    public function actionConfirmChangeMobile(){  
        $session = getSession();
        $cardaccount = CardAccount::model()->find('member_id='.Yii::app()->session['member']['id']);
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        if(isset($_POST['code'])){
            if($_POST['code']==$session['change_mobile']['code']){
                if(Yii::app()->db->createCommand()->update('card_accounts',array('mobile'=>$session['change_mobile']['mobile']),'id='.$cardaccount->id))
                {
                    unset($session['change_mobile']);
                    $this->redirect(getURL().'site/message/20');
                }
            }
            else {
                $this->redirect(getURL().'site/message/86');
            }
        }
        $this->render('confirm_change_mobile',array('cardaccount'=>$cardaccount,'member'=>$member,'change_mobile'=>$session['change_mobile']));
    }

        // lap lenh chuyen khoan buoc 1
    public function actionTransfer(){
        $account = CardAccountNoCheck::model()->findAll('member_id='.Yii::app()->session['member']['id']);
        $others = CardAccountNoCheck::model()->findAll('member_id<>'.Yii::app()->session['member']['id']);
        if(isset($_POST['data'])){
            //pr($_POST['data']); die;
            $data = $_POST['data'];
            $data['created'] = date('Y-m-d H:i:s');
            $data['modified'] = date('Y-m-d H:i:s');
            $account_send = CardAccountNoCheck::model()->find('numberaccount='.$data['account_send']); 
            $account_get = CardAccountNoCheck::model()->find('numberaccount='.$data['account_get']);
            $money = trim($data['money']);
            $money = str_replace(',', '', $money);
            if($account_send->blockade>0)
                $this->redirect(getURL().'site/message/63');
           // if(($account_send->money-50000) >=$money){ 
            if(($account_send->money-0) >=$money){ 
                $sum_transfer = Yii::app()->db->createCommand('select sum(money) from transfer where account_send='.$data['account_send'].' and created>="'.date('Y-m-d').'"')->queryScalar();
                if($sum_transfer<$account_send['max_transfer']){ 
                    if(($sum_transfer+$money)<$account_send['max_transfer']){
                        $session = getSession();
                        $session['transfer'] = array('data'=>$data,'account_send'=>$account_send,'account_get'=>$account_get);
                        $this->redirect(array('transfer2'));
                    } else 
                        $this->redirect(getURL().'site/message/52');
                } else {
                    $this->redirect(getURL().'site/message/51');
                }
            } else {
                $this->redirect(getURL().'site/message/50');
            }  
        }
        $this->render('transfer',array('account'=>$account,'others'=>$others));
    }
    
    // chuyen khoan buoc 2
    public function actionTransfer2(){
        $session = getSession();
        $transfer = $session['transfer'];
        $member_get = Member::model()->findByPk($transfer['account_get']['member_id']);
        if(isset($_POST['captcha'])){
            $captcha=Yii::app()->controller->createAction("captcha");
            $code = $captcha->verifyCode;
            $member_send = Member::model()->findByPk($transfer['account_send']['member_id']);
            if($code== trim($_POST['captcha'])){
                // gui email hoac sms
                $security = rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9).rand(0, 9); 
                $session['security']=array('value'=>$security,'created'=>date('Y-m-d H:i:s'));
                if($_POST['type_send']=='email'){
                    sendMail($member_send->email,'phong@gmail.com','Gửi mã giao dich chuyển khoản','Mã giao dịch chuyển khoản của quý khách là : '.$security);
                    $session['status']="Mã giao dịch đã được gửi dến địa chỉ email của quý khách";                    
                } else { // send sms
                    $session['status']="Mã giao dịch đã được gửi dến số điện thoại đã đăng ký nhân tin của quý khách";  
                    // lend sendsms
                }
                $this->redirect(array('transfer3'));
            }
        }       
        $this->render('transfer_2',array('transfer'=>$transfer,'member_get'=>$member_get));
    }
    
    // chuyen khoan buoc 3
    public function actionTransfer3(){
        $session = getSession();
        $transfer = $session['transfer'];
        $member_get = Member::model()->findByPk($transfer['account_get']['member_id']);
        if(isset($_POST['security'])&&isset($session['security'])){
           if(trim($_POST['security'])==$session['security']['value']){ 
               $created= mktime(
                       date('H',  strtotime($session['security']['created'])),
                       date('i',  strtotime($session['security']['created'])),
                       date('s',  strtotime($session['security']['created'])),
                       date('m',strtotime($session['security']['created'])),
                       date('d',strtotime($session['security']['created'])),
                       date('Y',strtotime($session['security']['created']))
                       ); 
               $gettime = mktime(
                       date('H'),
                       date('i'),
                       date('s'),
                       date('m'),
                       date('d'),
                       date('Y')
                       ); 
               $time_wait = $gettime - $created; // tong so giay doi              
               if($time_wait<=120){
                   $account_send = $transfer['account_send'];
                   $account_get = $transfer['account_get'];
                   $transfer['data']['money'] = str_replace(',', '', $transfer['data']['money']);
                   $account_send->money -= $transfer['data']['money'];
                    $account_get->money += $transfer['data']['money'];
                    if($account_send->save()&& $account_get->save()){   
                        $transfer_obj = new Transfer();
                        $transfer_obj->attributes= $transfer['data']; 
                        $transfer_obj->save();
                        $this->redirect (getURL().'site/message/11');
                    }
               }
           }
        }
         $this->render('transfer_3',array('transfer'=>$transfer,'member_get'=>$member_get));
    }

        // dang ky the thanh toan dien tu
    public function actionRegisterCard(){
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $card_exists = CardAccount::model()->find('member_id="'.$member->id.'"');
        if(!empty($card_exists))
             $this->redirect(getURL().'site/message/84');
        if(isset($_POST['data'])){           
            $cardaccount = new CardAccountNoCheck();
            $data['created']=  date('Y-m-d');
            $data['modified']=  date('Y-m-d');
            $data['member_id']= $member->id;
            $data['money'] = 0;
            $data['address'] = $_POST['data']['address'];
            if(!empty($_POST['data']['mobile']))
                $data['mobile']=$_POST['data']['mobile'];
            else 
                $data['mobile']=$_POST['data']['combomobile'];
            // tao so the tai khoan
            $numberCard=date('dmY');                    
            $numberCard .= substr($member->name, 0,4);
            $idmax=  Yii::app()->db->createCommand('select max(id) from members')->queryScalar();
            switch(strlen($idmax)){
            case 1:
                for($i=0;$i<3;$i++)
                        $numberCard .= rand (0, 9);
                break;
            case 2:
                for($i=0;$i<2;$i++)
                        $numberCard .= rand (0, 9);
                break;
            case 3:                  
                $numberCard .= rand (0, 9);
                break;
            }
            if(strlen($idmax)>4)
                $numberCard = substr ($numberCard, 0, 16-strlen($idmax));
            $numberCard .= $idmax;
            $data['numbercard']=$numberCard;
            $data['password_card']= addCode(rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9),3); // pass word 6 so ngau nhien mac dinh
            // tao so tai khoan gom 12 so, max dinh 3 so 8 dau tien
            $max_numberaccount = Yii::app()->db->createCommand("select max(numberaccount) from card_accounts")->queryScalar();
            if(empty($max_numberaccount))
                $data['numberaccount']=  '9686000000'.date('d');           
            else {
                 $data['numberaccount'] = ++$max_numberaccount;
            }
            
            do{
                $exists = CardAccount::model()->find('numberaccount='.$data['numberaccount']);
                if(!empty($exists))
                    $data['numberaccount']++;
            }while(!empty($exists));
            $cardaccount->attributes=$data;
            if($cardaccount->save()){
                sendMail($member->email,'phong@gmail.com','Gửi mật khẩu thẻ','Bạn đã kích hoạt thành công tài khoản.<BR> Số thẻ : '.createNumberCard($data['numbercard']).'<br>Mật khẩu : '.getString($data['password_card'],3).'<br>Số tài khoản : '.createNumberCard($data['numberaccount']).'<br> Bạn nên thay đổi mật khẩu mặc định này trước khi sử dụng.');
                //sendEmail($member->email,'phong@gmail.com','BeRichMart.189.vn','Gửi mật khẩu thẻ','Bạn đã kích hoạt thành công tài khoản.<BR> Số thẻ : '.createNumberCard($data['numbercard']).'<br>Mật khẩu : '.getString($data['password_card'],3).'<br>Số tài khoản : '.createNumberCard($data['numberaccount']).'<br> Bạn nên thay đổi mật khẩu mặc định này trước khi sử dụng.');
                $this->redirect(getURL().'site/message/10');
            }
           
        }
        $regulation = Regulation::model()->findByPk(2);
        $this->render('register_card',array('member'=>$member,'regulation'=>$regulation));
    }
    
    // nang cap thanh vien
    public function actionUpdateTVCT()
    {     
        $member = Member::model()->findByPk(Yii::app()->session['member']['id']);
        $this->render('update_tvct',array('member'=>$member));
    }
    
    protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='frm')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
        
    public function actionTestMail(){      
        $kq = sendMail('congnv@hoanggia.biz','phong_van372000@yahoo.com','chào mừng','Chúc mừng test mail thành công');
        echo $kq;
    }
    public function actionDelete($id){
        $node = new Nested_Set(Yii::app()->db);
       // $member = Member::model()->find('name='.$id);
       // $node->removeNode(183);
    }   
    
    // format so ajax
    public function actionCreateNumber(){
        $number = str_replace(',', '', $_POST['number']);
        echo number_format($number); 
    }
    
    public function beforeAction($action) {
            $sale=  Help::model()->find('status=1 order by id desc');
            $this->sale=$sale;
            return checkLoginMember($this);
        }
}
?>
