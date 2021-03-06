<?php

//云图库设置
define('CLOUDKEY', 'd9863161db6238a0e86342977754d611');
define('CLOUDSECRET', 'djpsCFRfspyF4zXbPJHuP163emIKKGbAy46cK1PuwBByQCb2Bw5poI1625ArauYQLxsmeftSM2wirz5LzOijSSGTFxv9bMX1vy2RDM8ZHphqTWFpBtkCfh0QZkUAHgc1');
define('CLOUDURL', 'http://47d58cf073ceca887ce0e4065bc0ea31.cn1.crs.easyar.com:8080/search');
header('Content-Type: application/javascript; charset=UTF-8');

// step 1: 获取浏览器上传的图片数据
$image = getHttpData();
if (!$image) showMsg(1, '未发送图片数据');

// step 2: 将图片数据发送云识别服务
$params = array(
	// GMT/UTC 日期与时间
	'date' => gmdate('Y-m-d\TH:i:s.123\Z'),
	'appKey' => CLOUDKEY,
	'image' => $image,
);
$params['signature'] = getSign($params, CLOUDSECRET);

$str = httpPost(CLOUDURL, json_encode($params));
if (!$str) showMsg(2, '网络错误');

// step 3: 解析识别结果，返回给浏览器使用
$obj = json_decode($str);
if (!$obj || (isset($obj->status) && $obj->status == 500)) {
	showMsg(2, '网络错误');
} else if ($obj->statusCode != 0) {
	showMsg(3, '未识别到目标');
} else {
	showMsg(0, $obj->result->target);
}

/**
 * 获取浏览器上传的图上数据
 * @return string
 */
function getHttpData() {
	$image = getPostImage();
	if (!$image) $image = getPostFile();

	return $image;
}

/**
 * WebAR使用
 * @return bool|string
 */
function getPostImage() {
	$data = @file_get_contents('php://input');
	if ($data) {
		$obj = json_decode($data);
		$data = $obj->image;
	}
	return $data;
}

/**
 * 微信小程序使用(上传文件处理)
 * @return string
 */
function getPostFile() {
	$data = '';
	if (isset($_FILES)) {
		foreach ($_FILES as $file) {
			if ($file['error'] == 0) {
				$data = base64_encode(@file_get_contents($file['tmp_name']));
				break;
			}
		}
	}

	return $data;
}

/**
 * 生成签名，使用sha1加密
 * @param $params
 * @param $cloudSecret
 * @return string
 */
function getSign($params, $cloudSecret) {
	//按字典顺序排序
	ksort($params);

	$tmp = array();
	foreach ($params as $key => $value) {
		$tmp[] = $key . $value;
	}
	$str = implode('', $tmp);

	return sha1($str . $cloudSecret);
}

function showMsg($code, $msg) {
	$arr = array(
		'statusCode' => $code,
		'result' => $msg,
	);
	echo json_encode($arr);
	exit;
}


function httpPost($url, $data) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json; charset=utf-8',
		'Content-Length: ' . strlen($data)));

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$str = curl_exec($ch);
	curl_close($ch);

	return $str;
}
