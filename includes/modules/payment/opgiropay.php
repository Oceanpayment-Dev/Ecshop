<?php

/**
 * ECSHOP Oceanpayment Giropay支付插件
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: pan $
 * $Id: opgiropay.php 17217 2014-10-26 09:36:08Z pan $
 */
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/payment/opgiropay.php';

if (file_exists($payment_lang))
{
	global $_LANG;

	include_once($payment_lang);
}

/**
 * 模块信息
 */
if (isset($set_modules) && $set_modules == true)
{
	$i = isset($modules) ? count($modules) : 0;

	/* 代码 */
	$modules[$i]['code'] = basename(__FILE__, '.php');

	/* 描述对应的语言项 */
	$modules[$i]['desc'] = 'opgiropay_desc';

	/* 是否支持货到付款 */
	$modules[$i]['is_cod'] = '0';

	/* 是否支持在线支付 */
	$modules[$i]['is_online'] = '1';

	/* 作者 */
	$modules[$i]['author']  = 'Oceanpayment Giropay支付';

	/* 网址 */
	$modules[$i]['website'] = 'http://www.oceanpayment.com.cn';

	/* 版本号 */
	$modules[$i]['version'] = '1.1.0';

	/* 配置信息 */
	$modules[$i]['config'] = array(
	array('name' => 'account', 'type' => 'text', 'value' => ''),
	array('name' => 'terminal', 'type' => 'text', 'value' => ''),
	array('name' => 'secureCode', 'type' => 'text', 'value' => ''),
	array('name' => 'giropayHandler', 'type' => 'text', 'value' => 'https://secure.oceanpayment.com/gateway/service/test'),
	);

	return;

}

class opgiropay
{
	const methods = 'Giropay';
	/**
	 * 构造函数
	 *
	 * @access  public
	 * @param
	 *
	 * @return void
	 */

	function opgiropay()
	{
	}

	function __construct()
	{
		$this->opgiropay();
	}

	/**
	 * 生成支付代码
	 * @param   array   $order  订单信息
	 * @param   array   $payment    支付方式信息
	 */
	function get_code($order, $payment)
	{

		
		//secureCode
		$secureCode = trim($payment['secureCode']);

		//账户号
		$account = trim($payment['account']);

		//终端号
		$terminal = trim($payment['terminal']);

		//支付方式
		$methods = self::methods;

		//备注
		$order_notes='';

		//返回地址
		$backUrl           = return_url(basename(__FILE__, '.php'));

		//支付人姓名 ecshop 没有firstName，lastName之分，只有一个name
		$billing_firstName = $order['consignee'];
		$billing_lastName = '(null)';

		//邮件
		$billing_email = isset($order['email']) ? $order['email'] : '13888888888@qq.com';

		//电话tel  可能是mobile也可能是tel，具体看模板
		//$billing_email = isset($order['mobile']) ? $order['mobile'] : '13888888888';
		$billing_phone = isset($order['tel']) ? $order['tel'] : '13888888888';

		//订单id
		$order_number = $order['order_sn'];

		//支付的币种
		$order_currency = 'USD';

		//支付的金额
		$order_amount = $order['order_amount'];


        //网店程序类型
		$cart_info = 'ecshop';
		
		//接口版本
		$cart_api  = 'V1.0.0';
		

        //如果国家，州，城市的值是ID，则调用getCsc();这方法
		$data = $this->getCsc($order['country'],$order['province'],$order['city']);
		$billing_country = $data['country'];
		$billing_state = $data['province'];
		$billing_city = $data['city'];
		

        //如果国家，州，城市的值不是ID，则直接从$order中获取
        /**
		$billing_country = $order['country'];
		$billing_state = $order['province'];
		$billing_city = $order['city'];
        **/

        //地址
		$billing_address = $order['address'];

		//邮编
		$billing_zip = $order['zipcode'];

        //邮编为空时，默认99999
        if (empty($billing_zip)){
        	$billing_zip = 99999;
        }
		
		
		//生成加密签名串
		$signsrc   = $account.$terminal.$backUrl.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$secureCode;
		$signValue = hash("sha256",$signsrc);
		
				//记录发送到oceanpayment的post log
	    $filedate = date('Y-m-d');
	    
	    $postdate = date('Y-m-d H:i:s');
	    
	    $newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
	    
	    $post_log = $postdate."[POST to Oceanpayment]\r\n" . 
	 	            "account = "           .$account . "\r\n".
	                "terminal = "          .$terminal . "\r\n".
         	        "backUrl = "           .$backUrl . "\r\n".
         	        "order_number = "      .$order_number . "\r\n".
         	        "order_currency = "    .$order_currency . "\r\n".
         	        "order_amount = "      .$order_amount . "\r\n".
         	        "billing_firstName = " .$billing_firstName . "\r\n".
         	        "billing_lastName = "  .$billing_lastName . "\r\n".
         	        "billing_email = "     .$billing_email . "\r\n".
         	        "billing_phone = "     .$billing_phone . "\r\n".
         	        "billing_country = "   .$billing_country . "\r\n".
         	        "billing_state = "     .$billing_state . "\r\n".
         	        "billing_city = "      .$billing_city . "\r\n".
         	        "billing_address = "   .$billing_address . "\r\n".
         	        "billing_zip = "       .$billing_zip . "\r\n".
         	        "methods = "           .$methods . "\r\n".
         	        "signValue = "         .$signValue . "\r\n".
         	        "cart_info = "         .$cart_info . "\r\n".
					"cart_api = "          .$cart_api . "\r\n".
					"order_notes = "       .$order_notes . "\r\n";
	    
	    $post_log = $post_log . "*************************************\r\n";
	    
	    $post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
	    
	    $filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
	    
	    fwrite($filename,$post_log);
	    
	    fclose($filename);
	    
	    fclose($newfile);
		
	    
		$def_url  = "<div style='text-align:center'><form  style='text-align:center;' method='post' name='giropay_checkout' action='".$payment['giropayHandler']."' onsubmit='payment_submit()'  >";
		$def_url .= "<input type='hidden' name='account' value='" . $account . "' />";
		$def_url .= "<input type='hidden' name='terminal' value='" . $terminal . "' />";
		$def_url .= "<input type='hidden' name='order_number' value='" . $order_number . "' />";
		$def_url .= "<input type='hidden' name='order_currency' value='" . $order_currency . "' />";
		$def_url .= "<input type='hidden' name='order_amount' value='" . $order_amount . "' />";
		$def_url .= "<input type='hidden' name='backUrl' value='" . $backUrl . "' />";
		$def_url .= "<input type='hidden' name='signValue' value='" . $signValue . "' />";
		$def_url .= "<input type='hidden' name='billing_firstName' value='" . $billing_firstName . "' />";
		$def_url .= "<input type='hidden' name='billing_lastName' value='" . $billing_lastName . "' />";
		$def_url .= "<input type='hidden' name='billing_email' value='" . $billing_email . "' />";
		$def_url .= "<input type='hidden' name='billing_phone' value='" . $billing_phone . "' />";
		$def_url .= "<input type='hidden' name='order_notes' value='" . $order_notes . "' />";
		$def_url .= "<input type='hidden' name='methods' value='" . $methods . "' />";
		$def_url .= "<input type='hidden' name='billing_country' value='" . $billing_country . "' />";
		$def_url .= "<input type='hidden' name='billing_state' value='" . $billing_state . "' />";
		$def_url .= "<input type='hidden' name='billing_city' value='" . $billing_city . "' />";
		$def_url .= "<input type='hidden' name='billing_address' value='" . $billing_address . "' />";
		$def_url .= "<input type='hidden' name='billing_zip' value='" . $billing_zip . "' />";
		$def_url .= "<input type='hidden' name='cart_info' value='" . $cart_info . "' />";
		$def_url .= "<input type='hidden' name='cart_api' value='" . $cart_api . "' />";
		$def_url .= "<input type='hidden' name='pages' value='" . $pages . "' />";
		/* 支付按钮 */
		$def_url .= "<input type='submit' name='submit' value='" . $GLOBALS['_LANG']['pay_button'] . "' />";
		$def_url .= "</form></div></br>";
		
		
		return $def_url;
	}
	/**
	 * 响应操作
	 */
	function respond()
	{
		$payment        	= get_payment(basename(__FILE__, '.php'));
		$account			= $_REQUEST["account"];
		$terminal			= $_REQUEST["terminal"];
		$payment_id			= $_REQUEST["payment_id"];
		$order_number		= trim($_REQUEST["order_number"]);
		$order_currency		= $_REQUEST["order_currency"];
		$order_amount		= $_REQUEST["order_amount"];
		$payment_status		= $_REQUEST["payment_status"];
		$payment_details	= $_REQUEST["payment_details"];
		$back_signValue		= $_REQUEST["signValue"];
		$order_notes		= $_REQUEST["order_notes"];
		$securecode 		= $payment['secureCode'];
		$card_number       	= $_REQUEST['card_number'];
		$payment_authType 	= $_REQUEST['payment_authType'];
		$payment_risk     	= $_REQUEST['payment_risk'];
		$local_signValue 	= hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
			          	$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
			
		if($_REQUEST['response_type'] == '1'){      //检测是否推送 1为推送 0为正常POST
			$logtype = '[PUSH]';
		}else{
			$logtype = '[Browser Return]';
		}
		
		
		
		//记录日志
		$filedate   = date('Y-m-d');
	
		$returndate = date('Y-m-d H:i:s');
			
		$newfile    = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
			
		$return_log = $returndate . $logtype . "\r\n".
				"response_type = "       . $_REQUEST['response_type'] . "\r\n".
				"account = "             . $_REQUEST['account'] . "\r\n".
				"terminal = "            . $_REQUEST['terminal'] . "\r\n".
				"payment_id = "          . $_REQUEST['payment_id'] . "\r\n".
				"order_number = "        . $_REQUEST['order_number'] . "\r\n".
				"order_currency = "      . $_REQUEST['order_currency'] . "\r\n".
				"order_amount = "        . $_REQUEST['order_amount'] . "\r\n".
				"payment_status = "      . $_REQUEST['payment_status'] . "\r\n".
				"payment_details = "     . $_REQUEST['payment_details'] . "\r\n".
				"signValue = "           . $_REQUEST['signValue'] . "\r\n".
				"order_notes = "         . $_REQUEST['order_notes'] . "\r\n".
				"card_number = "         . $_REQUEST['card_number'] . "\r\n".
				"payment_authType = "    . $_REQUEST['payment_authType'] . "\r\n".
				"payment_risk = "        . $_REQUEST['payment_risk'] . "\r\n";
	
		$return_log = $return_log . "*************************************\r\n";
			
		$return_log = $return_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
			
		$filename   = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
			
		fwrite($filename,$return_log);
	
		fclose($filename);
	
		fclose($newfile);
		
	
		
		if (strtolower($back_signValue) == strtolower($local_signValue)) {
			
			//是否推送，response_type:1则是推送，为0则是POST返回
			if($_REQUEST['response_type'] == 1){
			
				if(substr($payment_details,0,5) == '20061'){	 //排除订单号重复(20061)的交易
			
				}else{
					//正常处理流程
					if ($payment_status == "1") {
						//支付成功
						$log_id = get_order_id_by_sn($order_number);
						/* 改变订单状态 */
						order_paid($log_id);
						$backUrl = return_url(basename(__FILE__, '.php'))."&success=1";
						echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';				
					} else {		
						//支付失败
						$backUrl = return_url(basename(__FILE__, '.php'))."&success=0";
						echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';
					}
				}
					
			}elseif($_REQUEST['response_type'] == 0){
					
				//正常 POST返回
				if(substr($payment_details,0,5) == '20061'){	 //排除订单号重复(20061)的交易
					$backUrl = return_url(basename(__FILE__, '.php'))."&success=0";
					echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';	
				}else{
					//正常处理流程
					if ($payment_status == "1") {
						//支付成功
						$log_id = get_order_id_by_sn($order_number);
						/* 改变订单状态 */
						order_paid($log_id);
						$backUrl = return_url(basename(__FILE__, '.php'))."&success=1";
						echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';			
					} else {
						//支付失败
						$backUrl = return_url(basename(__FILE__, '.php'))."&success=0";
						echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';			
					}			
				}				
			}
			
		}else{
			//加密验证失败
			$backUrl = return_url(basename(__FILE__, '.php'))."&success=0";
			echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';
		}

	}

	/**
	 * 将变量值不为空的参数组成字符串
	 * @param   string   $strs  参数字符串
	 * @param   string   $key   参数键名
	 * @param   string   $val   参数键对应值
	 */
	function append_param($strs,$key,$val)
	{
		if($strs != "")
		{
			if($key != '' && $val != '')
			{
				$strs .= '&' . $key . '=' . $val;
			}
		}
		else
		{
			if($val != '')
			{
				$strs = $key . '=' . $val;
			}
		}
		return $strs;
	}
	function getCsc($country,$state,$city)
	{
		$data = array();
		if(!empty($country))
		{
			$data['country']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$country);
		}
		else
		{
			$data['country']='Undefined';
		}
		if(!empty($state))
		{
			$data['province']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$state);
		}

		else
		{
			$data['province']='Undefined';
		}
		if(!empty($city))
		{
			$data['city']=$country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$city);
		}

		else
		{
			$data['city']='Undefined';
		}

		return $data;
	}

}

?>