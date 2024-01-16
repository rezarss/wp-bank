<?php

/****************************************
*         وب سرویس دو منظوره ارسال پیامک
*              سامانه پیامک رنگینه
*             sms.rangine.ir
* ارسال پترن: متن پیامک باید به شکل زیر ترتیب داده شود
* "patterncode:AAAAAAAA;name:Hadi;family:mollaei;variable:value"
* در واقع بعد از معرفی کد پترن در پارامتر اول، متغیرهای پترن را یکی یکی آورده و بعد از هر کدام دو نقطه گذاشته و مقدارش را درج کنید. بین هر پارمتر هم نقطه ویرگول انگلیسی درج شود. آخر پیام هم نقطه ویرگول نباشه
* message ="patterncode:d7vg6e0os8;verification-code:3359";
****************************************/

function send_sms_ippanel($numbers, $message) {
	
	$username = 'rezarss';
	$password = 'Rz-1596321';
	
	$panel = "ippanel.com";
	$param = array(
		'uname' => $username, // نام کاربری پنل
		'pass' => $password, // پسوورد پنل
		'from' => '5000125475', // شماره خط ارسال کننده
		'to' => json_encode(explode(',', $numbers)), // جدا کننده شماره موبایل ها با کاما (,)
		'message' => $message,
		'op' => 'send',
	);

	//If text is pattern
	if (substr($param['message'], 0, 11) === "patterncode") {
		$message = trim($param['message']);
		$message = str_replace("\r\n", ';', $message);
		$message = str_replace("\n", ';', $message);
		$splited = explode(';', $message);
		$pattern_code = explode(':', $splited[0])[1];
		unset($splited[0]);
		$input_data = array();
		foreach ($splited as $parm) {
			$splited_parm = explode(':', $parm, 2);
			$input_data[$splited_parm[0]] = $splited_parm[1];
		}

		$url = $panel . "/patterns/pattern?username=" . $param['uname'] . "&password=" . $param['pass'] . "&from=" . $param['from'] . "&to=" . $param['to'] . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=" . $pattern_code;
		$handler = curl_init($url);
		curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($handler, CURLOPT_TIMEOUT, 30); //timeout in seconds
		$response = curl_exec($handler);
		if (curl_errno($handler)) {
			$status = 'failed';
			$result = curl_error($handler);
		} else
		{
			$response2 = json_decode($response);
			if (is_array($response2)) {
				$res_code = $response2[0];
				$res_data = $response2[1];
				$status = 'failed';
				$result = getPanelErrors($res_code);
			} else
			{
				$status = $result = 'sent';
				$res_data = $response;
			}
		}
	} else
	{
		//ارسال معمولی پیامک
		$handler = curl_init($panel . "/services.jspd");
		curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($handler, CURLOPT_POSTFIELDS, $param);
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($handler, CURLOPT_TIMEOUT, 30); //timeout in seconds
		$response = curl_exec($handler);
		$response2 = json_decode($response);
		$res_code = $response2[0];
		$res_data = $response2[1];

		if ($res_code == '0') {
			$status = $result = 'sent';
		} else
		{
			$status = 'failed';
			$result = getPanelErrors($res_code);
		}
	}
	$result = array('status' => $status, 'result' => $result, 'res_data' => $res_data);
	return $result['result'];
	//return json_encode($result, JSON_UNESCAPED_UNICODE);
}

//	لیست خطاهای وب سرویس
function getPanelErrors($error) {
	$errorCodes = array(
		'0' => 'عملیات با موفقیت انجام شده است.',
		'1' => 'متن پیام خالی می باشد.',
		'2' => 'کاربر محدود گردیده است.',
		'3' => 'خط به شما تعلق ندارد.',
		'4' => 'گیرندگان خالی است.',
		'5' => 'اعتبار کافی نیست.',
		'7' => 'خط مورد نظر برای ارسال انبوه مناسب نمیباشد.',
		'9' => 'خط مورد نظر در این ساعت امکان ارسال ندارد.',
		'98' => 'حداکثر تعداد گیرنده رعایت نشدهه است.',
		'99' => 'اپراتور خط ارسالی قطع می باشد.',
		'21' => 'پسوند فایل صوتی نامعتبر است.',
		'22' => 'سایز فایل صوتی نامعتبر است.',
		'23' => 'تعداد تالش در پیام صوتی نامعتبر است.',
		'100' => 'شماره مخاطب دفترچه تلفن نامعتبر می باشد.',
		'101' => 'شماره مخاطب در دفترچه تلفن وجود دارد.',
		'102' => 'شماره مخاطب با موفقیت در دفترچه تلفن ذخیره گردید.',
		'111' => 'حداکثر تعداد گیرنده برای ارسال پیام صوتی رعایت نشده است.',
		'131' => 'تعداد تالش در پیام صوتی باید یکبار باشد.',
		'132' => 'آدرس فایل صوتی وارد نگردیده است.',
		'301' => 'از حرف ویژه در نام کاربری استفاده گردیده است.',
		'302' => 'قیمت گذاری انجام نگریدهه است.',
		'303' => 'نام کاربری وارد نگردیده است.',
		'304' => 'نام کاربری قبال انتخاب گردیده است.',
		'305' => 'نام کاربری وارد نگردیده است.',
		'306' => 'کد ملی وارد نگردیده است.',
		'307' => 'کد ملی به خطا وارد شده است.',
		'308' => 'شماره شناسنامه نا معتبر است.',
		'309' => 'شماره شناسنامه وارد نگردیده است.',
		'310' => 'ایمیل کاربر وارد نگردیده است.',
		'311' => 'شماره تلفن وارد نگردیده است.',
		'312' => 'تلفن به درستی وارد نگردیده است.',
		'313' => 'آدرس شما وارد نگردیده است.',
		'314' => 'شماره موبایل را وارد نکرده اید.',
		'315' => 'شماره موبایل به نادرستی وارد گردیده است.',
		'316' => 'سطح دسترسی به نادرستی وارد گردیده است.',
		'317' => 'کلمه عبور وارد نگردیده است.',
		'404' => 'پترن در دسترس نیست.',
		'455' => 'ارسال در آینده برای کد بالک ارسالی لغو شد.',
		'456' => 'کد بالک ارسالی نامعتبر است.',
		'458' => 'کد تیکت نامعتبر است.',
		'964' => 'شما دسترسی نمایندگی ندارید.',
		'962' => 'نام کاربری یا کلمه عبور نادرست می باشد.',
		'963' => 'دسترسی نامعتبر می باشد.',
		'971' => 'پترن ارسالی نامعتبر است.',
		'970' => 'پارامتر های ارسالی برای پترن نامعتبر است.',
		'972' => 'دریافت کننده برای ارسال پترن نامعتبر می باشد.',
		'992' => 'ارسال پیام از ساعت 8 تا 23 می باشد.',
		'993' => 'دفترچه تلفن باید یک آرایه باشد',
		'994' => 'لطفا تصویری از کارت بانکی خود را از منو مدارک ارسال کنید',
		'995' => 'جهت ارسال با خطوط اشتراکی سامانه، لطفا شماره کارت بانکیه خود را به دلیل تکمیل فرایند احراز هویت از بخش ارسال مدارک ثبت نمایید.',
		'996' => 'پترن فعال نیست.',
		'997' => 'شما اجازه ارسال از این پترن را ندارید.ه',
		'998' => 'کارت ملی یا کارت بانکی شما تایید نشده است.',
		'1001' => 'فرمت نام کاربری درست نمی باشد)حداقله ۵ کاراکتر، فقط حروف و اعداد(.',
		'1002' => 'گذر واژه خیلی ساده می باشد)حداقل ۸ کاراکتر بوده و نام کاربری،',
		'ایمی' => ' و شماره موبایل در آن وجود نداشته باشد(.',
		'1003' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
		'1004' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
		'1005' => 'مشکل در ثبت، با پشتیبانی تماس بگیرید.',
		'1006' => 'تاریخ ارسال پیام برای گذشته می باشد، لطفا تاریخ ارسال پیام را به درستی وارد نمایید.ه',
	);
	return (isset($errorCodes[$error])) ? $errorCodes[$error] : 'اشکال تعریف نشده با کد :' . $error;
}