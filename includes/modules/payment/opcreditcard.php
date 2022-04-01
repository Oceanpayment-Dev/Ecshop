<?php

/**
 * ECSHOP Oceanpayment 信用卡支付插件
 */
include(ROOT_PATH."mobiledetect.php");
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/payment/opcreditcard.php';

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
	$modules[$i]['desc'] = 'opcreditcard_desc';

	/* 是否支持货到付款 */
	$modules[$i]['is_cod'] = '0';

	/* 是否支持在线支付 */
	$modules[$i]['is_online'] = '1';

	/* 作者 */
	$modules[$i]['author']  = 'Oceanpayment';

	/* 网址 */
	$modules[$i]['website'] = 'http://www.oceanpayment.com.cn';

	/* 版本号 */
	$modules[$i]['version'] = '1.3.0';

	/* 配置信息 */
	$modules[$i]['config'] = array(
	array('name' => 'account', 'type' => 'text', 'value' => ''),
	array('name' => 'terminal', 'type' => 'text', 'value' => ''),
	array('name' => 'secureCode', 'type' => 'text', 'value' => ''),
	array('name' => 'creditcardHandler', 'type' => 'text', 'value' => 'https://secure.oceanpayment.com/gateway/service/test'),
	array('name' => 'Inside', 'type' => 'select', 'value' => ''),
	array('name' => '3DService', 'type' => 'select', 'value' => ''),
	array('name' => '3DTerminal', 'type' => 'text', 'value' => ''),
	array('name' => '3DSecureCode', 'type' => 'text', 'value' => ''),
	array('name' => '3DCurrencies', 'type' => 'text', 'value' => ''),
	array('name' => '3DCurrenciesValue', 'type' => 'text', 'value' => ''),
	);

	return;

}

class opcreditcard
{
	const methods = 'Credit Card';
	/**
	 * 构造函数
	 *
	 * @access  public
	 * @param
	 *
	 * @return void
	 */

	function opcreditcard()
	{
	}

	function __construct()
	{
		$this->opcreditcard();
	}

	/**
	 * 生成支付代码
	 * @param   array   $order  订单信息
	 * @param   array   $payment    支付方式信息
	 */
	function get_code($order, $payment)
	{
		//内嵌
		$Inside = $payment['Inside'];    
		
		
		
		//支付的币种
		$order_currency = $order['currency'];
		//支付的金额
		$order_amount = $order['order_amount'];
		

		//非3D交易
		$_SESSION['is_3d'] = 0;
			
		//判断是否启用3D功能
		if($payment['3DService'] == 2){
			//检验是否需要3D验证
			$validate_arr = $this->validate3D($order_currency, $order_amount, $payment);
		}else{
			$validate_arr['terminal'] = trim($payment['terminal']);
			$validate_arr['securecode'] = trim($payment['secureCode']);
		}
			
		
		
		//账户号
		$account = trim($payment['account']);
		//终端号
		$terminal = $validate_arr['terminal'];
		//secureCode
		$secureCode = $validate_arr['securecode'];
		//支付方式
		$methods = self::methods;
		//订单id
		$order_number = $order['order_sn'];		
		//备注
		$order_notes = '';
		//返回地址
		$backUrl = return_url(basename(__FILE__, '.php'));
		//服务器回调地址
		$noticeUrl = $GLOBALS['ecs']->url() . 'op_notice.php?code=' . basename(__FILE__, '.php');
		//账单人名
		$billing_firstName = $this->OceanHtmlSpecialChars($order['consignee']);
		//账单人姓
		$billing_lastName = $this->OceanHtmlSpecialChars($order['consignee']);
		//账单人email
		$billing_email = isset($order['email']) ? $order['email'] : 'ecshop@ecshop.com';
		//账单人电话
		$billing_phone = isset($order['tel']) ? $order['tel'] : $order['mobile'];
        //网店程序类型
		$cart_info = 'ecshop';	
		//接口版本
		$cart_api = 'V1.4.0';
        //如果国家，州，城市的值是ID，则调用getCsc();这方法
		$data = $this->getCountryDetails($order['country'],$order['province'],$order['city']);
		//账单人国家
		$billing_country = $data['country'];
		//账单人州(可不提交)
		$billing_state = $data['province'];
		//账单人城市
		$billing_city = $data['city'];
		//账单人地址
		$billing_address = $order['address'];
		//账单人邮编,如果邮编为空，就默认999999
		$billing_zip = !empty($order['zipcode']) ? $order['zipcode'] : 'N/A';
        //产品名称
		$productName = !empty(get_goods_name_by_id($order['order_id'])) ? get_goods_name_by_id($order['order_id']) : 'N/A';
		if (!defined('EC_CHARSET') || EC_CHARSET == 'utf-8')
		{
		    $productName = ecs_iconv('utf-8', 'gbk', $productName);
		}
		//产品数量
		$order_id = $order['order_id'];
		$sql = 'SELECT goods_number FROM ' . $GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'";
		$goods_number = $GLOBALS['db']->getCol($sql);
		$productNum = !empty($goods_number) ? implode(',', $goods_number) : 'N/A';
        //产品sku
		$sql = 'SELECT goods_sn FROM ' . $GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'";
		$goods_sn = $GLOBALS['db']->getCol($sql);
		$productSku = !empty($goods_sn) ? implode(',', $goods_sn) : 'N/A';

        //如果国家，州，城市的值不是ID，则直接从$order中获取
        /**
        //账单人国家
		$billing_country = $order['country'];
		//账单人州(可不提交)
		$billing_state = $order['province'];
		//账单人城市
		$billing_city = $order['city'];
        **/

		//支付页面类型
		$detect = new mobiledetect();
		if($detect->isiOS()){
			$pages = 1;
		}elseif($detect->isMobile()){
			$pages = 1;
		}elseif($detect->isTablet()){
			$pages = 0;
		}else{
			$pages = 0;
		}
		

		//生成加密签名串
		$signsrc = $account.$terminal.$backUrl.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$secureCode;
		$signValue = hash("sha256",$signsrc);
		
		//记录发送到oceanpayment的post log
	    $filedate = date('Y-m-d');
	    
	    $postdate = date('Y-m-d H:i:s');
	    
	    $newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
	    
	    $post_log = $postdate."[POST to Oceanpayment]\r\n" . 
	 	            "account = "           .$account . "\r\n".
	                "terminal = "          .$terminal . "\r\n".
         	        "backUrl = "           .$backUrl . "\r\n".
         	        "noticeUrl = "         .$noticeUrl . "\r\n".
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
         	        "productName = "       .$productName . "\r\n".         	        
         	        "productNum = "        .$productNum . "\r\n".         	        
         	        "productSku = "        .$productSku . "\r\n".         	        
         	        "methods = "           .$methods . "\r\n".
         	        "signValue = "         .$signValue . "\r\n".
         	        "cart_info = "         .$cart_info . "\r\n".
					"cart_api = "          .$cart_api . "\r\n".
					"pages = "             .$pages . "\r\n".
					"order_notes = "       .$order_notes . "\r\n";
	    
	    $post_log = $post_log . "*************************************\r\n";
	    
	    $post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
	    
	    $filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
	    
	    fwrite($filename,$post_log);
	    
	    fclose($filename);
	    
	    fclose($newfile);
		
	    
		$def_url  = "<div style='text-align:center'><form  style='text-align:center;' method='post' name='creditcard_checkout' action='".$payment['creditcardHandler']."' onsubmit='payment_submit()'  >";
		$def_url .= "<input type='hidden' name='account' value='" . $account . "' />";
		$def_url .= "<input type='hidden' name='terminal' value='" . $terminal . "' />";
		$def_url .= "<input type='hidden' name='order_number' value='" . $order_number . "' />";
		$def_url .= "<input type='hidden' name='order_currency' value='" . $order_currency . "' />";
		$def_url .= "<input type='hidden' name='order_amount' value='" . $order_amount . "' />";
		$def_url .= "<input type='hidden' name='backUrl' value='" . $backUrl . "' />";
		$def_url .= "<input type='hidden' name='noticeUrl' value='" . $noticeUrl . "' />";
		$def_url .= "<input type='hidden' name='signValue' value='" . $signValue . "' />";
		$def_url .= "<input type='hidden' name='billing_firstName' value='" . $billing_firstName . "' />";
		$def_url .= "<input type='hidden' name='billing_lastName' value='" . $billing_lastName . "' />";
		$def_url .= "<input type='hidden' name='billing_email' value='" . $billing_email . "' />";
		$def_url .= "<input type='hidden' name='billing_phone' value='" . $billing_phone . "' />";
		$def_url .= "<input type='hidden' name='order_notes' value='" . $order_notes . "' />";
		$def_url .= "<input type='hidden' name='methods' value='" . $methods . "' />";
		$def_url .= "<input type='hidden' name='productName' value='" . $productName . "' />";
		$def_url .= "<input type='hidden' name='productNum' value='" . $productNum . "' />";
		$def_url .= "<input type='hidden' name='productSku' value='" . $productSku . "' />";
		$def_url .= "<input type='hidden' name='billing_country' value='" . $billing_country . "' />";
		$def_url .= "<input type='hidden' name='billing_state' value='" . $billing_state . "' />";
		$def_url .= "<input type='hidden' name='billing_city' value='" . $billing_city . "' />";
		$def_url .= "<input type='hidden' name='billing_address' value='" . $billing_address . "' />";
		$def_url .= "<input type='hidden' name='billing_zip' value='" . $billing_zip . "' />";
		$def_url .= "<input type='hidden' name='cart_info' value='" . $cart_info . "' />";
		$def_url .= "<input type='hidden' name='cart_api' value='" . $cart_api . "' />";
		$def_url .= "<input type='hidden' name='pages' value='" . $pages . "' />";
		
		if($Inside==1){
			/* 开启内嵌 */
			$def_url .= "<input type='submit' name='submit' value='" . $GLOBALS['_LANG']['pay_button'] . "' />";
			$def_url .= '</form></div></br>';
			$def_url .= '<iframe width="100%" height="300px"  scrolling="auto" style="border:none ; margin: 0 auto; overflow:hidden;" id="ifrm_creditcard_checkout" name="ifrm_creditcard_checkout"></iframe>' . "\n";
			$def_url .= '<script type="text/javascript">' . "\n";
			$def_url .= 'function payment_submit(){' . "\n";
			$def_url .= 'if (window.XMLHttpRequest) {' . "\n";
			$def_url .= 'document.creditcard_checkout.target="ifrm_creditcard_checkout";' . "\n";
			$def_url .= '}' . "\n";
			$def_url .= 'document.creditcard_checkout.action="'.$payment['creditcardHandler'].'";' . "\n";
			$def_url .= 'document.creditcard_checkout.submit();' . "\n";
			$def_url .= 'window.status = "'.$payment['creditcardHandler'].'";' . "\n";
			$def_url .= '}' . "\n";					
			$def_url .= '</script>' . "\n";
			
		}else{
			/* 支付按钮 */
			$def_url .= "<input type='submit' name='submit' value='" . $GLOBALS['_LANG']['pay_button'] . "' />";
			$def_url .= "</form></div></br>";
		}
		
		return $def_url;
	}
	
	
	/**
	 * 响应操作
	 */
	function respond()
	{
		$payment = get_payment(basename(__FILE__, '.php'));

		
		//返回商户号
		$account          = $_REQUEST['account'];
		//返回终端号
		$terminal         = $_REQUEST['terminal'];
		
		
		//匹配终端号   判断是否3D交易
		if($terminal == trim($payment['terminal'])){
			$securecode = trim($payment['secureCode']);
		}elseif($terminal == trim($payment['3DTerminal'])){
			//3D
			$securecode = trim($payment['3DSecureCode']);
		}else{
			$securecode = '';
		}
	
		//返回Oceanpayment 的支付唯一号
		$payment_id       = $_REQUEST['payment_id'];
		//返回网站订单号
		$order_number     = $_REQUEST['order_number'];
		//返回交易币种
		$order_currency   = $_REQUEST['order_currency'];
		//返回支付金额
		$order_amount     = $_REQUEST['order_amount'];
		//返回支付状态
		$payment_status   = $_REQUEST['payment_status'];
		//返回支付详情
		$payment_details  = $_REQUEST['payment_details'];
		//返回交易安全签名
		$back_signValue   = $_REQUEST['signValue'];
		//返回备注
		$order_notes      = $_REQUEST['order_notes'];
		//未通过的风控规则
		$payment_risk     = $_REQUEST['payment_risk'];
		//返回支付信用卡卡号
		$card_number      = $_REQUEST['card_number'];
		//返回支付方式
		$methods      	  = $_REQUEST['methods'];
		//返回消费者国家
		$payment_country  = $_REQUEST['payment_country'];
		//返回交易类型
		$payment_authType = $_REQUEST['payment_authType'];
		//返回解决方案
		$payment_solutions = $_REQUEST['payment_solutions'];
		
		//响应代码
		//用于支付结果页面显示响应代码
		$getErrorCode = explode(':', $payment_details);
		$ErrorCode = $getErrorCode[0];
		
		
		$local_signValue = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
			          	$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
			
		

		$_SESSION['payment_solutions'] = $payment_solutions;
		$_SESSION['payment_details'] = $payment_details;
		

	
		//交易推送日志
		$logType = '[Browser Return]';
		$this->returnLog($logType);
		
		
		
		if (strtolower($back_signValue) == strtolower($local_signValue)) {
						
			//正常 POST返回
			if($ErrorCode == 20061){	 //排除订单号重复(20061)的交易
				$success = 0;	
			}else{
				//正常处理流程
				if($payment_status == 1){
					//支付成功
					$log_id = get_order_id_by_sn($order_number);
					//改变订单状态
					order_paid($log_id, PS_PAYED, $payment_details);
					
					$success = 1;
				}elseif($payment_status == -1){
					//待处理
					if ($payment_authType == 1) {
						//判断是否预授权
						$log_id = get_order_id_by_sn($order_number);
						//改变订单状态
						order_paid($log_id);
						
						$success = 1;
					}
				}else{		
					//支付失败
					$success = 0;
				}			
			}				
			
		}else{
			//加密验证失败
			$success = 0;
		}
		
		
		
		
		$backUrl = return_url(basename(__FILE__, '.php'))."&success=".$success;
		echo '<script type="text/javascript">parent.location.replace("'.$backUrl.'");</script>';

	}

	
	
	
	/**
	 * 服务器响应操作
	 */
	function notice()
	{
		
		//输入流
		$xml_str = file_get_contents("php://input");
	
		//判断返回的输入流是否为xml
		if($this->xml_parser($xml_str)){
			$xml = simplexml_load_string($xml_str);
				
			//把推送参数赋值到$_REQUEST
			$_REQUEST['response_type']	  = (string)$xml->response_type;
			$_REQUEST['account']		  = (string)$xml->account;
			$_REQUEST['terminal'] 	      = (string)$xml->terminal;
			$_REQUEST['payment_id'] 	  = (string)$xml->payment_id;
			$_REQUEST['order_number']     = (string)$xml->order_number;
			$_REQUEST['order_currency']   = (string)$xml->order_currency;
			$_REQUEST['order_amount']     = (string)$xml->order_amount;
			$_REQUEST['payment_status']   = (string)$xml->payment_status;
			$_REQUEST['payment_details']  = (string)$xml->payment_details;
			$_REQUEST['signValue'] 	      = (string)$xml->signValue;
			$_REQUEST['order_notes']	  = (string)$xml->order_notes;
			$_REQUEST['card_number']	  = (string)$xml->card_number;
			$_REQUEST['payment_authType'] = (string)$xml->payment_authType;
			$_REQUEST['payment_risk'] 	  = (string)$xml->payment_risk;
			$_REQUEST['methods'] 	  	  = (string)$xml->methods;
			$_REQUEST['payment_country']  = (string)$xml->payment_country;
			$_REQUEST['payment_solutions']= (string)$xml->payment_solutions;
			
			
			$payment = get_payment(basename(__FILE__, '.php'));
			
			
			//匹配终端号   判断是否3D交易
			if($_REQUEST['terminal'] == trim($payment['terminal'])){
				$securecode = trim($payment['secureCode']);
			}elseif($_REQUEST['terminal'] == trim($payment['3DTerminal'])){
				//3D
				$securecode = trim($payment['3DSecureCode']);
			}else{
				$securecode = '';
			}
			
			
			
		}
	
		
		if($_REQUEST['response_type'] == 1){
			
			
			//交易推送日志
			$logType = '[PUSH]';
			$this->returnLog($logType);
	
			//签名数据
			$local_signValue = hash("sha256",$_REQUEST['account'].$_REQUEST['terminal'].$_REQUEST['order_number'].$_REQUEST['order_currency'].$_REQUEST['order_amount'].$_REQUEST['order_notes'].$_REQUEST['card_number'].
					$_REQUEST['payment_id'].$_REQUEST['payment_authType'].$_REQUEST['payment_status'].$_REQUEST['payment_details'].$_REQUEST['payment_risk'].$securecode);
				
				
			//响应代码
			$getErrorCode = explode(':', $_REQUEST['payment_details']);
			$ErrorCode = $getErrorCode[0];
			
			
			if (strtolower($_REQUEST['signValue']) == strtolower($local_signValue)) {
					
				
				if($ErrorCode == 20061){
					//排除订单号重复(20061)的交易
				}else{
					//正常处理流程
					if($_REQUEST['payment_status'] == 1){
						//支付成功
						$log_id = get_order_id_by_sn($_REQUEST['order_number']);
						//改变订单状态
						order_paid($log_id, PS_PAYED, $_REQUEST['payment_details']);
	
					}elseif($_REQUEST['payment_status'] == -1){
						//待处理
						if ($_REQUEST['payment_authType'] == 1) {
							//判断是否预授权
							$log_id = get_order_id_by_sn($_REQUEST['order_number']);
							//改变订单状态
							order_paid($log_id, PS_PAYED, $_REQUEST['payment_details']);
	
						}
					}else{
						//支付失败
					}
				}
						
				echo "receive-ok ";
				exit;
					
			}else{
				//加密验证失败
			}
			
		}

	
	}
	
	
	
	
	/**
	 * 记录日志
	 */
	public function returnLog($logType){
	
		$filedate   = date('Y-m-d');
	
		$returndate = date('Y-m-d H:i:s');
			
		$newfile    = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
			
		$return_log = $returndate . $logType . "\r\n".
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
				"methods = "   		  	 . $_REQUEST['methods'] . "\r\n".
				"payment_country = "   	 . $_REQUEST['payment_country'] . "\r\n".
				"payment_authType = "    . $_REQUEST['payment_authType'] . "\r\n".
				"payment_risk = "        . $_REQUEST['payment_risk'] . "\r\n";
	
		$return_log = $return_log . "*************************************\r\n";
			
		$return_log = $return_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
			
		$filename   = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
			
		fwrite($filename,$return_log);
	
		fclose($filename);
	
		fclose($newfile);
	
	}
	
	
	
	
	
	/**
	 * 检验是否需要3D验证
	 */
	public function validate3D($order_currency, $order_amount, $payment){
	
		//是否需要3D验证
		$is_3d = 0;


		//获取3D功能下各个的币种
		$currencies_value_str = trim($payment['3DCurrencies']);
		$currencies_value = explode(';', $currencies_value_str);
		//获取3D功能下各个的金额
		$amount_value_str = trim($payment['3DCurrenciesValue']);
		$amount_value = explode(';', $amount_value_str);
		 
		$amountValidate = array_combine($currencies_value, $amount_value);
		 
		if($amountValidate){
			//判断金额是否为空
			if(isset($amountValidate[$order_currency])){
				//判断3D金额不为空
				//判断订单金额是否大于3d设定值
				if($order_amount >= $amountValidate[$order_currency]){
					//需要3D
					$is_3d = 1;
				}
			}
		}

	
		if($is_3d ==  0){
	
			//终端号
			$terminal = trim($payment['terminal']);
			//securecode
			$securecode = trim($payment['secureCode']);
				
		}elseif($is_3d == 1){
				
			//3D终端号
			$terminal= trim($payment['3DTerminal']);
			//3D securecode
			$securecode = trim($payment['3DSecureCode']);
			//是3D交易
			$_SESSION['is_3d'] = 1;
		}
	
	
		$validate_arr['terminal'] = $terminal;
		$validate_arr['securecode'] = $securecode;
	
		return $validate_arr;
	
	}
	
	
	
	
	/**
	 * 根据ID值获取名称
	 * @param 国家ID $country
	 * @param 州ID $state
	 * @param 城市ID $city
	 */
	function getCountryDetails($country,$state,$city)
	{
		$data = array();
		
		if(!empty($country)){
			$data['country'] = $country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$country);
		}else{
			$data['country'] = 'N/A';
		}
		
		if(!empty($state)){
			$data['province'] = $country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$state);
		}else{
			$data['province'] = 'N/A';
		}
		
		if(!empty($city)){
			$data['city'] = $country=$GLOBALS['db']->getOne("select region_name from ".$GLOBALS['ecs']->table('region')." where region_id=".$city);
		}else{
			$data['city'] = 'N/A';
		}

		return $data;
	}
	
	
	/**
	 *  判断是否为xml
	 *
	 */
	function xml_parser($str){
		$xml_parser = xml_parser_create();
		if(!xml_parse($xml_parser,$str,true)){
			xml_parser_free($xml_parser);
			return false;
		}else {
			return true;
		}
	}
	
	
	
	/**
	 * 钱海支付Html特殊字符转义
	 */
	function OceanHtmlSpecialChars($parameter){
	
		//去除前后空格
		$parameter = trim($parameter);
	
		//转义"双引号,<小于号,>大于号,'单引号
		$parameter = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$parameter);
	
		return $parameter;
	
	}
	


}

?>