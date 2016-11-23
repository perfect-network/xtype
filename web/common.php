<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function sendEmail($to = '' , $title = '' , $content = '') {
	$mail = new \email\PHPMailer();
	$mail->IsSMTP();
    $mail->Host = config('mail_host');
    $mail->SMTPAuth = config('mail_smtpauth'); 
    $mail->Username = config('mail_username'); 
    $mail->Password = config('mail_password');
    $mail->From = config('mail_from');
    $mail->FromName = config('mail_fromname'); 
    $mail->AddAddress($to,"免流VPN客户");
    $mail->WordWrap = 50; 
    $mail->IsHTML(config('mail_ishtml'));
    $mail->CharSet = config('mail_charset');
    $mail->Subject =$title;
    $mail->Body = $content;
    $mail->AltBody = "这是一个纯文本的身体在非营利的HTML电子邮件客户端";
    return($mail->Send());
}


function getIp(){
    $ip='未知IP';
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        return is_ip($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:$ip;
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        return is_ip($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$ip;
    }else{
        return is_ip($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:$ip;
    }
}

function is_ip($str){
    $ip=explode('.',$str);
    for($i=0;$i<count($ip);$i++){  
        if($ip[$i]>255){  
            return false;  
        }  
    }  
    return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',$str);  
}

function createRandomStr($length){ 
    $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $strlen = 62; 
    while($length > $strlen){ 
        $str .= $str; 
        $strlen += 62; 
    } 
    $str = str_shuffle($str); 
    return substr($str,0,$length); 
}