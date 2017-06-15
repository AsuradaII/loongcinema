<?php

/**
/**
 * 火烈鸟系统出票接口
 * @author esyy <esyy@qq.com>
 */
**/

class LoongcinemaModel extends Model {
	/*/test
	private $pa = array(
		'app'  => '000####',
		'key'  => '##########',
		'url'  =>'http://test.##############/api/ticket/v1/',
	);*/
	//生产地址
	private $pa = array(
		'app'  => '#########',
		'key'  => '#############',
		'url'  => 'http://##################/api/ticket/v1/',
	);
	
	/* 请求方法 */
	public function https_request($url, $data = null)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	/**
	 * 1、把http请求的将除“sign”外的所有参数按key进行ASCII码升序排列。
		2、再将排序后的参数用&拼接起来，按UTF-8进行URL编码。
		3、将分配给渠道的通讯密钥加在编码后的字符串的前面和后面。
		4、把得到的字符串进行MD5（RFC 1321标准），得到sign。
		5、把sign加在请求参数中进行请求调用。
	 */
	public function sign($api,$api_data){
		$pa = $this->pa;
		$appkey = $pa['key'];
		$api_url = $pa['url'].$api.'.json';
		$api_data['channelCode'] = $pa['app'];
		//正序
		ksort($api_data);
		//连接参数名+参数值
		$sign_str = http_build_query($api_data);
		//密钥+参数名+参数值+密钥 md5生成签名
		//print_r($appkey.urlencode($sign_str).$appkey);
		$sign = md5($appkey.urlencode(urldecode($sign_str)).$appkey);
		
		$url = $api_url.'?'.$sign_str.'&sign='.$sign;
		$output = $this->https_request($url);
		file_put_contents(C('LOG_DIR').'Huolie_api'.date('Ymd').'.log',date("Y-m-d H:i:s ").$url."\r\n".$output."\r\n",FILE_APPEND);
		return json_decode($output,TRUE);
	}
	    
    /* 7.查询影厅座位列表
	7.1.接口名称
	querySeats
	7.2.接口说明
	查询指定影厅的座位列表。
	7.3.请求参数
	名称	类型	是否必须	描述
	cinemaCode	String	必须	影院编码
	hallCode	String	必须	影厅编码
	  */
    public function querySeats($cinemaCode,$hallCode) {
		$param = array(
			'cinemaCode' =>$cinemaCode,
			'hallCode' =>$hallCode,
		);
		$data = $this->sign('querySeats', $param);
		return $data;
    }
	    
    /* 12.查询场次座位列表
	12.1.接口名称
	queryShowSeats
	12.2.接口说明
	查询指定场次的所有状态或指定状态的座位列表。
	12.3.请求参数
	名称	类型	是否必须	描述
	channelShowCode	String	必须	渠道场次编码
	status	String	非必须	座位状态（0：不可售 1：可售）。不传默认查询所有状态的座位列表。
	  */
    public function queryShowSeats($channelShowCode,$status='') {
		$param = array(
			'channelShowCode' =>$channelShowCode,
		);
		if($status!=='')$param['status'] = $status;
		$data = $this->sign('queryShowSeats', $param);
		return $data;
    }
	
    /* 锁座13.锁定座位
	13.1.接口名称
	lockSeats
	13.2.接口说明
	锁定指定场次的座位。锁定座位后将产生订单，用于支付后确认订单。每次允许锁座的数量取决于影院地面系统的设置，建议每次最多允许锁定4个座位。
	13.3.请求参数
	名称	类型	是否必须	描述
	channelShowCode	String	必须	渠道场次编码
	seatCodes	String	必须	座位编码（多个用逗号分隔）
	channelOrderCode	String	必须	渠道订单号（长度不超过30个字符）
	  */
    public function lockSeats($channelShowCode,$seatCodes,$channelOrderCode) {
		$param = array(
			'channelShowCode' =>$channelShowCode,
			'seatCodes' =>$seatCodes,
			'channelOrderCode' =>$channelOrderCode,
		);
		$data = $this->sign('lockSeats', $param);
		return $data;
    }
	
    /* 取消锁座14.释放座位
	14.1.接口名称
	releaseSeats
	14.2.接口说明
	释放已锁定的座位。如果锁定座位后放弃支付，可以释放已锁定的座位，方便其他用户购买这些座位。
	14.3.请求参数
	名称	类型	是否必须	描述
	orderCode	String	必须	平台订单号
	 */
    public function releaseSeats($orderCode) {
		$param = array(
			'orderCode' =>$orderCode,
		);
		$data = $this->sign('releaseSeats', $param);
		return $data;
    }
	
    /* 出票 
	15.确认订单
	15.1.接口名称
	submitOrder
	15.2.接口说明
	锁座后确认订单。平台将调用影院地面系统接口来确认订单，最终确认订单是否成功取决于影院地面系统。
	该接口是关键的交易接口，为了避免账务上的偏差，渠道方调用该接口时或调用接口后务必得到确切的成功或失败的结果。正常情况下，平台接收到影院地面系统返回的确认订单成功的结果后直接返回确认订单成功给渠道方，此为最终结果；网络异常或其他特殊情况下，平台跟影院地面系统确认订单时可能无法即时获取到确切的出票结果，此时平台将返回确认订单失败的结果（非最终出票结果）给渠道方，渠道方接收到确认订单失败的结果后应轮询 查询订单 接口（建议间隔1分钟查询一次），直到获取到出票成功或出票失败的最终订单出票状态。如渠道方未轮询到最终的订单出票状态就直接退款，而最后平台出票成功，相应损失由渠道方承担。
	15.3.请求参数
	名称	类型	是否必须	描述
	orderCode	String	必须	平台订单号
	mobile	String	必须	手机号码
	orderSeats	String	必须	订单座位（格式为：座位编码:销售价:结算价，多个座位用逗号分隔。销售价即实际的销售价格，结算价是渠道和影院合同签订的结算价。销售价和结算价都以元为单位，保留2位小数）
	*/
    public function submitOrder($orderCode,$mobile,$orderSeats) {
		$param = array(
			'orderCode' =>$orderCode,
			'mobile' =>$mobile,
			'orderSeats' =>$orderSeats,
		);
		$data = $this->sign('submitOrder', $param);
		return $data;
    }
	/*
	16.查询订单
	16.1.接口名称
	queryOrder
	16.2.接口说明
	查询指定的订单。通过查询订单接口可以查询到订单的状态及订单信息。订单的状态分为6种：
	1.未支付：锁定座位成功，待支付。
	2.已取消：锁定座位成功超过锁定时间未支付或已释放座位。
	3.已支付：确认订单请求到达平台后设置订单为已支付状态。
	4.出票成功：调用影院地面系统确认订单成功。
	5.出票失败：调用影院地面系统确认订单失败。
	6.已退票：通知影院地面系统退票成功。
	当渠道方调用 确认订单 接口发生不可预料异常时，务必调用该接口来获取确认订单的结果。如果订单处于“已支付”状态，表明仍在等待影院地面系统的确认订单结果；当订单处于“出票成功”或“出票失败”状态时，表明已从影院地面系统得到了明确的结果。调用方应在得到明确的结果后再进行后续的业务处理，避免账务上出现偏差。
	16.3.请求参数
	名称	类型	是否必须	描述
	orderCode	String	必须	平台订单号
	*/
    public function queryOrder($orderCode) {
		$param = array(
			'orderCode' =>$orderCode,
		);
		$data = $this->sign('queryOrder', $param);
		return $data;
    }
}

