<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(dirname(__DIR__) . '/bank-plugin/assets/php/ippanel.php');
require_once(dirname(__DIR__) . '/bank-plugin/assets/php/jdf.php'); 
 
if (!function_exists('is_plugin_active')) 
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');

////////////////////////////////////////////////////////////////////////////// woocommerce wallet function 
if (is_plugin_active('woocommerce-wallet/main.php')){
 require_once(dirname(__DIR__) . '/woocommerce-wallet/includes/classes/Wallet.php');
 
  //Wallet::get_balance($user_id);
  function fsww_set_balance($user_id, $amount) {

        global $wpdb;

        if (!Wallet::wallet_exist($user_id)) {

            Wallet::create_wallet($user_id, 0, 0, 'unlocked');

        }

        $data = array(
            'balance' => FS_WC_Wallet::encrypt_decrypt('encrypt', $amount)
        );

        $where = array(
            'user_id' => $user_id
        );

        $wpdb->update("{$wpdb->prefix}fswcwallet", $data, $where);

    }
}
////////////////////////////////////////////////////////////////////////////// end of woocommerce wallet function
  
  
////////////////////////////////////////////////////////////////////////////// Database functions

function is_table_exists($table_name) {
    global $wpdb;

	// check if table exists
	$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

	if (! $wpdb->get_var($query) == $table_name)
	    return false;
	else
	    return true;
}

function create_table($table_name, $fields) {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name ($fields) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	$success = empty($wpdb->last_error);
	return true;
}

function get_db_row($table, $col, $value) {
  global $wpdb;
  $result = $wpdb->get_results("SELECT * FROM $table WHERE $col = '$value'");
  return $result;
}

////////////////////////////////////////////////////////////////////////////// jwt api tokens
function base64url_encode($str) {
  return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
}

function get_user_for_frontend($user_id) {
    $user = get_user_by('id', $user_id);
    return array('user_id' => $user_id, 'user_login' => $user->user_login, 'user_email' => $user->user_email, 'user_nicename' => $user->user_nicename, 'user_registered' => $user->user_registered, 'display_name' => $user->display_name, 'phone_number' => get_usermeta($user_id, 'digt_countrycode').get_usermeta($user_id, 'digits_phone_no'));
}

function generate_jwt($user_id, $headers = array("alg" => "HS256", "typ" => "JWT")) {
  $headers_encoded = base64url_encode(json_encode($headers));
  
  // payload  
  $payload = get_user_for_frontend($user_id);
  $payload['exp'] = time() + (365 * 24 * 60 * 60);
  $payload_encoded = base64url_encode(json_encode($payload));

  $signature = hash_hmac('SHA256', "$headers_encoded.$payload_encoded", JWT_SECRET, true);
  $signature_encoded = base64url_encode($signature);

  $jwt = "$headers_encoded.$payload_encoded.$signature_encoded";

  return $jwt;
}

function is_jwt_valid($jwt) {
  // split the jwt
  $tokenParts = explode('.', $jwt);
  $header = base64_decode($tokenParts[0]);
  $payload = base64_decode($tokenParts[1]);
  $signature_provided = $tokenParts[2];

  // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
  $expiration = json_decode($payload)->exp;
  $is_token_expired = ($expiration - time()) < 0;

  // build a signature based on the header and payload using the secret
  $base64_url_header = base64url_encode($header);
  $base64_url_payload = base64url_encode($payload);
  $signature = hash_hmac('SHA256', $base64_url_header . "." . $base64_url_payload, JWT_SECRET, true);
  $base64_url_signature = base64url_encode($signature);

  // verify it matches the signature provided in the jwt
  $is_signature_valid = ($base64_url_signature === $signature_provided);
  
  if ($is_token_expired || !$is_signature_valid) {
    return false;
  } else {
    return true;
  }
}

function get_jwt_payload($jwt) {
  // split the jwt
  $tokenParts = explode('.', $jwt);
  $payload = base64_decode($tokenParts[1]);

  // check the expiration time - note this will cause an error if there is no 'exp' claim in the jwt
  return json_decode($payload);
}


/////////////////////////
function x_response_http($message, $status_code, $code = 'error') {
  http_response_code($status_code);
  return array('code' => $code, 'message' => $message, 'data' => array('status' => $status_code));
}
function is_rest() {
  if (defined('REST_REQUEST') && REST_REQUEST || isset($_GET['rest_route']) && strpos($_GET['rest_route'], '/', 0) === 0)
    return true;

    // (#3)
    global $wp_rewrite;
    if ($wp_rewrite === null) $wp_rewrite = new WP_Rewrite();

    // (#4)
    $rest_url = wp_parse_url(trailingslashit(rest_url()));
    $current_url = wp_parse_url(add_query_arg(array()));
    return strpos($current_inurl['path'] ?? '/', $rest_url['path'], 0) === 0;
  }

  function current_api_endpoint() {
    $path = wp_parse_url(add_query_arg(array()));

    if (strpos($path['path'], 'wp-json'))
      $x = substr($path['path'], strpos($path['path'], 'wp-json') + strlen('wp-json'));
    else
      $x = str_replace('%2F', '/', end(explode('=', $path['query'])));


    return array('namespace' => explode("/", $x)[1], 'full_path' => $x);

  }
  ////////////////////////////////////////////////////////////////////////////// jwt api tokens
  

////////////////////////////////////////////////////////////////////////////// other functions

function generateRandomNumber($length) {
    $min = pow(10, ($length - 1));
    $max = pow(10, $length) - 1;

    return mt_rand($min, $max);
}


function generateRandomString($length) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $string = '';

    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $string;
}


////////////////////////////////////////////////////////////////////////////// date functions

function timestamp_to_datetime($timestamp) {
    // Convert the timestamp to a formatted date and time string
    $date = date('Y-m-d', $timestamp);
    $time = date('H:i:s', $timestamp);
    return $date . ' ' . $time;
}

function timestamp_to_jalali($timestamp) {
    $datetime = timestamp_to_datetime($timestamp);
    $datetime_arr = explode(' ', $datetime);
    $date_arr = explode('-', $datetime_arr[0]);
    return gregorian_to_jalali($date_arr[0], $date_arr[1], $date_arr[2],'-') . ' ' . $datetime_arr[1];
}

function iran_holidays($separator = '-') {
  $holidays = array(
    'jalali' => array(
      '01' . $separator . '01' => 'جشن نوروز',
      '01' . $separator . '02' => 'عید نوروز',
      '01' . $separator . '03' => 'عید نوروز',
      '01' . $separator . '04' => 'عید نوروز',
      '01' . $separator . '12' => 'روز جمهوری اسلامی ایران',
      '01' . $separator . '13' => 'روز طبیعت',
      '03' . $separator . '14' => 'رحلت حضرت امام خمینی (ره)',
      '03' . $separator . '15' => 'قیام خونین 15 خرداد (1342 هـ ش)',
      '11' . $separator . '12' => 'پیروزی انقلاب اسلامی ایران',
      '12' . $separator . '29' => 'روز ملی شدن صنعت نفت ایران (1329 هـ ش)',
    ),
    'hijri' => array(
      '01' . $separator . '09' => 'تاسوعای حسینی',
      '01' . $separator . '10' => 'عاشورای حسینی',
      '02' . $separator . '20' => 'اربعین حسینی',
      '02' . $separator . '29' => 'رحلت حضرت رسول اکرم صلی الله علیه و آله ( 11 هـ ق )ـ شهادت حضرت امام حسن مجتبی علیه السلام ( 50 هـ ق)',
      '02' . $separator . '20' => 'شهادت حضرت امام رضا علیه السلام (203 هـ ق )',
      '03' . $separator . '08' => 'شهادت حضرت امام حسن عسگری (ع) (260 هـ ق) و آغاز ولایت حضرت ولی‌عصر(عج)',
      '03' . $separator . '17' => 'میلاد حضرت رسول اکرم صلی الله علیه و آله (53 سال قبل از هجرت ) – میلاد حضرت امام جعفر صادق علیه‌السلام مؤسس مذهب جعفری (83 هـ ق)',
      '06' . $separator . '03' => 'شهادت حضرت فاطمة زهرا سلام الله علیها (11 هـ ق)',
      '07' . $separator . '13' => 'ولادت حضرت امام علی علیه السلام (23 سال قبل از هجرت )',
      '07' . $separator . '27' => 'مبعث حضرت رسول اکرم صلی الله علیه و آله (13 سال قبل از هجرت)',
      '08' . $separator . '15' => 'ولادت حضرت قائم عجل الله تعالی فرجه (255 هـ ق)',
      '09' . $separator . '21' => 'شهادت حضرت علی علیه السلام (40 هـ ق)',
      '10' . $separator . '01' => 'عید سعید فطر',
      '10' . $separator . '02' => 'تعطیلی به مناسبت روز بعد از عید سعید فطر',
      '10' . $separator . '25' => 'شهادت حضرت امام جعفر صادق علیه السلام (148 هـ ق)',
      '12' . $separator . '10' => 'عید سعید قربان',
      '12' . $separator . '18' => 'عید سعید غدیرخم (10 هـ ق)',
    )
  );
  return $holidays;
}

///////////////////////////////////////////////////////////////////////////////////////////////
// number_english_persian
function ToPersian($number) // $x = "ali"   => $x[0]
{
  $persian = ['۰',
    '۱',
    '۲',
    '۳',
    '۴',
    '۵',
    '۶',
    '۷',
    '۸',
    '۹'];
  $english = ['0',
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9'];
  return str_replace($english, $persian, $number);
};

// number_persian_english
function ToEnglish($number) {
  $persian = ['۰',
    '۱',
    '۲',
    '۳',
    '۴',
    '۵',
    '۶',
    '۷',
    '۸',
    '۹'];
  $persian2 = ['٠',
    '١',
    '٢',
    '٣',
    '۴',
    '۵',
    '۶',
    '٧',
    '٨',
    '٩']; // apple
  $english = ['0',
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9'];
  $number = str_replace($persian, $english, $number);
  return str_replace($persian2, $english, $number);
};

function clean($input) {
  return addslashes(htmlspecialchars(ToEnglish(trim($input))));
}

function remove_from_first($filter = array('+98', '98', '+', '0'), $str) {
  foreach ($filter as $f) {
    if (substr($str, 0, strlen($f)) == $f) {
      $str = substr($str, strlen($f));
    }
  }
  return $str;
}
///////////////////////////////////////////////////////////////////////////////////////////////

/////////////////// Hijri Qamari
{
  function julianToHijri($julianDay, $separator, $diff_jalali_hijri = 0) {
    $y = 10631.0 / 30.0;
    $epochAstro = 1948084;
    $shift1 = 8.01 / 60.0;
    $z = $julianDay - $epochAstro;
    $cyc = floor($z / 10631.0);
    $z = $z - 10631 * $cyc;
    $j = floor(($z - $shift1) / $y);
    $z = $z - floor($j * $y + $shift1);
    $year = 30 * $cyc + $j;
    $month = (int)floor(($z + 28.5001) / 29.5);
    if ($month === 13) {
      $month = 12;
    }
    $day = $z - floor(29.5001 * $month - 29) + (int)$diff_jalali_hijri;

    return (int)$year . $separator . (int)$month . $separator . (int)$day;
    return array('year' => (int) $year, 'month' => (int) $month, 'day' => (int) $day);
  }

  function gregorianToJulian($year, $month, $day) {
    if ($month < 3) {
      $year -= 1;
      $month += 12;
    }
    $a = floor($year / 100.0);
    $b = ($year === 1582 && ($month > 10 || ($month === 10 && $day > 4)) ? -10 :
      ($year === 1582 && $month === 10 ? 0 :
        ($year < 1583 ? 0 : 2 - $a + floor($a / 4.0))));
    return floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $b - 1524;
  }



  function gregorianToHijri($date, $diff_jalali_hijri = 0) {

    // $date format => yyyy - mm - dd
    if (strpos($date, '-'))
      $separator = '-';
    elseif (strpos($date, '/'))
      $separator = '/';
    else
      $separator = false;

    $date = explode($separator, $date);

    $jd = gregorianToJulian((int)$date[0], (int)$date[1], (int)$date[2]);
    return julianToHijri($jd, $separator, $diff_jalali_hijri);
  }

}
/////////////////// End of Hijri Qamari

function jalali_to_hijri($date, $diff_jalali_hijri = -1) {
  // $date format => yyyy - mm - dd
  if (strpos($date, '-'))
    $separator = '-';
  elseif (strpos($date, '/'))
    $separator = '/';
  else
    $separator = false;

  $date = explode($separator, $date);


  $jalali_to_gregorian = jalali_to_gregorian((int)$date[0], (int)$date[1], (int)$date[2], $separator);

  $result = explode($separator, gregorianToHijri($jalali_to_gregorian, $diff_jalali_hijri));

  $yyyy = $result[0];
  $mm = $result[1];
  $dd = $result[2];

  if ($mm < 10 && $mm[0] !== '0')
    $mm = '0' . $mm;

  if ($dd < 10 && $dd[0] !== '0')
    $dd = '0' . $dd;


  $result = $yyy . $separator . $mm . $separator . $dd;

  return $result;
}


function is_date_off($jalali_date) {
  if (strpos($jalali_date, '-'))
    $separator = '-';
  elseif (strpos($jalali_date, '/'))
    $separator = '/';
  else
    $separator = false;

  if (!$separator)
    return 'تاریخ صحیح را وارد کنید';

  $jalali_day = explode($separator, $jalali_date)[1] . '-' . explode($separator, $jalali_date)[2];
  $hijri_day = explode($separator, jalali_to_hijri($jalali_date))[1] . '-' . explode($separator, jalali_to_hijri($jalali_date))[2];

  $holidays = iran_holidays($separator);

  $result = array('status' => false, 'description' => '');
  foreach ($holidays as $calendarType => $offDays) {
    foreach ($offDays as $offDay => $occasion) {
      //echo "offDay: $offDay = jalali_day: $jalali_day = qamari_day: $qamari_day ---------------";
      if (($calendarType === 'jalali' && $offDay === $jalali_day) || ($calendarType === 'hijri' && $offDay === $hijri_day)) {
        $result['status'] = true;
        $result['description'] = $occasion;
      }
    }
  }

  return $result;
}

function get_farsi_weekdays($jalali_date) {
  $splitted_jalali_date = explode('-', $jalali_date);
  $year = $splitted_jalali_date[0];
  $month = $splitted_jalali_date[1];
  $day = $splitted_jalali_date[2];

  // $date: string => '2022-04-14'
  $date = jalali_to_gregorian($year, $month, $day, '-');


  $timestamp = strtotime($date);
  $jalali_date_day = strtolower(date('l', $timestamp)); // $jalali_date_day = saturday or sunday or ...

  foreach (WEEKDAYS as $en_day => $fa_day) {
    if ($jalali_date_day === $en_day)
      return $fa_day;
  }

}

function get_day_of_date($jalali_date) {
  $splitted_jalali_date = explode('-', $jalali_date);
  $year = $splitted_jalali_date[0];
  $month = $splitted_jalali_date[1];
  $day = $splitted_jalali_date[2];

  // $date: string => '2022-04-14'
  $date = jalali_to_gregorian($year, $month, $day, '-');


  $timestamp = strtotime($date);
  $day = strtolower(date('l', $timestamp));

  $day_detail = [];
  $day_detail['date'] = $jalali_date;
  $day_detail['day'] = $day;

  if ($day === 'saturday' || $day === 'monday' || $day === 'wednesday' || $day === 'friday')
    $day_detail['oddeven'] = 'even';
  elseif ($day === 'sunday' || $day === 'tuesday' || $day === 'thursday')
    $day_detail['oddeven'] = 'odd';
  else
    $day_detail['oddeven'] = 'friday';


  return $day_detail;
}

function time_to_seconds($time) {

  $arr = explode(':', $time);
  if (count($arr) === 3) {
    return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
  }
  return ($arr[0] * 60 + $arr[1]) * 60;

}

function diff_two_times_in_minutes($time1, $time2) {

  $time_min1 = time_to_seconds($time1) / 60;
  $time_min2 = time_to_seconds($time2) / 60;

  return abs($time_min2 - $time_min1);

}

function get_queue_starts_of_shift($time1, $time2, $divide_in_min) {

  $start = time_to_seconds($time1) < time_to_seconds($time2) ? $time1 : $time2;
  $end = time_to_seconds($time2) > time_to_seconds($time1) ? $time2 : $time1;

  $diff = diff_two_times_in_minutes($start, $end);


  $result = [];

  $result[] = $start;

  for ($i = 1; $i <= floor($diff / $divide_in_min); $i++) {
    $new_start = date('H:i', strtotime($start. ' +' . $divide_in_min . ' minutes'));
    if ($new_start !== $end)
      $result[] = $new_start;
    $start = $new_start;
  }

  return $result;
}
////////////////////////////////////////////////////////////////////////////// end of other functions


function validateDate($date, $format = 'Y-m-d H:i:s') {

  $d = DateTime::createFromFormat($format, $date);

  return $d && $d->format($format) == $date;

}


function getVarName(&$var, $definedVars = null) {
  $definedVars = (!is_array($definedVars) ? $GLOBALS : $definedVars);
  $val = $var;
  $rand = 1;
  while (in_array($rand, $definedVars, true)) {
    $rand = md5(mt_rand(10000, 1000000));
  }
  $var = $rand;

  foreach ($definedVars as $dvName => $dvVal) {
    if ($dvVal === $rand) {
      $var = $val;
      return $dvName;
    }
  }

  return null;
}


//////////////////////////////////////////////////////////////////////////////////////// Login and Register

function is_mobile_exists($mobile) {
  $mobile = clean($mobile);
  $mobile = remove_from_first(array('+', '0', ' '), $mobile);

  global $wpdb;
  $usermeta_table = $wpdb->prefix . "usermeta";
  $res = $wpdb->get_results("SELECT user_id FROM $usermeta_table WHERE meta_key = 'digits_phone' AND meta_value = '+$mobile'");

  if (count($res)) {
    $user_id = $res[0]->user_id;
    return get_user_by('id', $user_id);
  } else
    return false;
}

function is_user_exists($user_login) {
  $user = get_user_by('id', $user_login);
  if (!$user)
    $user = get_user_by('login', $user_login);
  if (!$user)
    $user = get_user_by('email', $user_login);
  if (!$user)
    $user = is_mobile_exists($user_login);

  if (!$user)
    return false;

  // adding meta
  $user->data->first_name = get_user_meta($user->data->ID, 'first_name', true);
  $user->data->last_name = get_user_meta($user->data->ID, 'last_name', true);

  return $user;
}


//Login with password => Login contains: user_login, user_email meta_mobile:+9891....
function login_with_password($user_login, $user_password) {

  $user = is_user_exists($user_login);

  $username = $user->data->user_login;


  if (!is_wp_error(wp_authenticate($username, $user_password))) {
    wp_clear_auth_cookie();
    wp_set_current_user ($user->ID);
    wp_set_auth_cookie ($user->ID);
    session_set_cookie_params(3600 * 24 * 30); // seconds * hours * days = 1 month
    session_start();
    $_SESSION['login'] = $user->data;

    $res['status'] = true;
    $res['result'] = $user->data;
    return $res;
  } else {
    $res['status'] = false;
    $res['result'] = 'user_login wrong';
    return $res;
  }
}

function wpse_update_password_field( $user_id, $hashed_password) {
   global $wpdb;

   return $wpdb->update( 
        $wpdb->users,
        array( 'user_pass' => $hashed_password ),
        array( 'ID' => $user_id )
   );
}

function register_user($first_name, $last_name, $password, $country_code, $mobile, $email, $user_ref = 0) {
  global $wpdb;
  
  // check if user already signed up by email
  $email_exists = is_user_exists($email);
  if ($email_exists) {
    $res['status'] = false;
    $res['result'] = 'emailExists';
    return $res;
  }
  
  $mobile = ltrim($mobile, "0"); 
  
  // check if user already signed up by phone
  $mobile_exists = is_user_exists($country_code . $mobile);
  if ($mobile_exists) {
    $res['status'] = false;
    $res['result'] = 'mobileExists';
    return $res;
  }
   
  

  // start creating user
  $user_id = wp_insert_user(array(
    //'user_login' => explode('@',$email)[0],
    'user_login' => $email,  
    'user_pass' => $password,
    'user_email' => $email,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'display_name' => $first_name . ' ' . $last_name,
    //'user_nicename' => $first_name . ' ' . $last_name, // not working
    'nickname' => $first_name . ' ' . $last_name,
    'role' => get_option('default_role'),
  ));

  if (!is_wp_error($user_id)) {
    // once again update user pass
    wpse_update_password_field( $user_id, md5($password));
    // adding user meta
    add_user_meta($user_id, 'digits_phone', "+" . $country_code . $mobile);
    add_user_meta($user_id, 'digt_countrycode', "+" . $country_code);
    add_user_meta($user_id, 'digits_phone_no', $mobile);
    
    // add user_ref
    if(!get_user_by("id", (int)$user_ref))
        $user_ref = 0;
    $add_ref = $wpdb->insert('user_ref', array('user_id' => $user_id, 'ref_id' => (int)$user_ref));
    
    // result
    $res['status'] = true;
    $res['result'] = $user_id;
    return $res;
  } else {
    $res['status'] = false;
    $res['result'] = $user_id->get_error_message();
    return $res;
  }
}

/////////////////////// OTP

function generate_otp($user, $otpQtyNumber = 5, $expire = 1 * 60, $table = 'otp_authentication') {
    $otpQtyNumberArrayMax = [];
    for($i=1;$i<=$otpQtyNumber;$i++) {
        array_push($otpQtyNumberArrayMax, 9);
    }
    
    $otpQtyNumberArrayMin = [];
    for($i=1;$i<=$otpQtyNumber-1;$i++) {
        array_push($otpQtyNumberArrayMin, 0); 
    }
    
    $otp = rand((int)'1'.implode($otpQtyNumberArrayMin), (int)implode($otpQtyNumberArrayMax));
     
      
    // add to db
    global $wpdb;
    $insert = $wpdb->insert($table, array(
        'otp' => $otp,
        'user' => $user,
        'expire' => time() + $expire
    ));
    
    if (!$insert)
        return false;
    
    return $otp;
}

function get_otp($otp, $user, $table = 'otp_authentication') {
    global $wpdb;
    $otp_sql = $wpdb->get_results("SELECT * FROM `$table` WHERE `otp` = $otp AND `user` = '$user' ORDER BY `creation_time` LIMIT 1");
    return count($otp_sql) ? $otp_sql[0] : false;
}

function verify_otp($otp, $user, $table = 'otp_authentication') {
    $get_otp = get_otp($otp, $user, $table);
    
    if ($otp == $get_otp->otp && $user === $get_otp->user && time() <= $get_otp->expire)
        return true;
    else
        return false;
    
}

/////////////////////// END OF OTP

function send_OTP($mobile, $otpQtyNumber = 4, $otpTimeOut = 1 * 60, $pattern = IPPANEL_PATTERN) {
  $mobile = clean($mobile);

  if ($otpQtyNumber == 4)
    $otp = rand(1000, 9999);
  elseif ($otpQtyNumber == 5)
    $otp = rand(10000, 99999);
  elseif ($otpQtyNumber == 6)
    $otp = rand(100000, 999999);

  $otp_time_creation = time();

  session_start();
  $_SESSION['otp']['otp'] = $otp;
  $_SESSION['otp']['creation-time'] = $otp_time_creation;
  $_SESSION['otp']['expire'] = $otp_time_creation + $otpTimeOut;
  $_SESSION['otp']['user'] = $mobile;


  $blog_title = get_bloginfo('name');
  $result_sms = false;

  //////////////// send sms
  // get activated smsProvider
  $message = "patterncode:$pattern;code:$otp";
  $result_sms = send_sms_ippanel($mobile, $message);


  return $result_sms;
}
function verify_mobile_OTP($mobile, $user_otp) {
  session_start();

  if ($user_otp == $_SESSION['otp']['otp'] && $mobile === $_SESSION['otp']['user'] && time() <= $_SESSION['otp']['expire'])
    return true;
  else
    return false;

}

function send_mail_OTP($email, $otpQtyNumber = 5, $expire = 1 * 60) {

  $otp = generate_otp($email, $otpQtyNumber, $expire);
  if (!$otp)
    $result_sms = false;
    

  $blog_title = get_bloginfo('name');
  $result_sms = false;

  //////////////// send sms
  // get activated smsProvider
  $message = "Your code is " . $otp;
  $result_sms = send_mail($email, 'Verification Code', $message);
  $result_sms['otpQtyNumber'] = $otpQtyNumber; 
  $result_sms['email'] = $email; 
  $result_sms['otp'] = $otp; 


  return $result_sms;
}

function send_forget_password_mail_OTP($email, $otpQtyNumber = 5, $expire = 1 * 60) {

  $otp = generate_otp($email, $otpQtyNumber, $expire);
  if (!$otp)
    $result_sms = false;
    

  $blog_title = get_bloginfo('name');
  $result_sms = false;

  //////////////// send sms
  // get activated smsProvider
  $message = "لینک تغییر کلمه عبور: ";
  $message .= BANK_DOOMAIN . "login/forget-password?email=$email&otp=$otp";
  
  // send mail
  $send_mail = send_mail($email, 'تغییر کلمه عبور', $message, 'noreply@rebex.ir', 'Bank');
    
  if ($send_mail['status'])
    $result_sms = true;

  return $result_sms;
}

function verify_mail_OTP($email, $user_otp) {
  session_start();
  
  if ($user_otp == $_SESSION['otp']['otp'] && $email === $_SESSION['otp']['user'] && time() <= $_SESSION['otp']['expire'])
    return true;
  else
    return false;

}

function send_mail($to, $subject, $body, $from = EMAIL_FROM, $from_name = EMAIL_FROM_NAME, $host = EMAIL_HOST, $username = EMAIL_USERNAME, $password = EMAIL_PASSWORD) {
    
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $res['status'] = false;
        $res['message'] = 'email is invalid';
        return $res;
    }

    require 'assets/php/PHPMailer-master/src/Exception.php';
    require 'assets/php/PHPMailer-master/src/PHPMailer.php';
    require 'assets/php/PHPMailer-master/src/SMTP.php';
    
    $mail = new PHPMailer();  // create a new object
    $mail->CharSet = "UTF-8";
    $mail->Encoding = 'base64';
    $mail->isSMTP(); // enable SMTP
    //$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
    $mail->SMTPAuth = true;  // authentication enabled
    $mail->SMTPSecure = 'none'; // secure transfer enabled REQUIRED for GMail
    $mail->SMTPAutoTLS = false;
    $mail->Host = $host;
    $mail->Port = 25;
    
    $mail->Username = $username;
    $mail->Password = $password;
    
    $mail->SetFrom($from, $from_name);
    $mail->Subject = $subject;
    $mail->Body = $body; 
    $mail->AddAddress($to);
    
    if(!$mail->Send()) {
        $res['status'] = false;
        $res['message'] = $mail->ErrorInfo;
    } else {
        $res['status'] = true;
        $res['message'] = 'sent';
    }
    
    return $res;
}

////////////////////////////////////  website structure functions

function get_currency($symbol, $network) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM `currencies` WHERE symbol = '$symbol' AND network = '$network'");
    
    if(!count($res))
        return false;
        
    return $res[0];
}

function get_user_refs($user_id) {
    global $wpdb;

    $level = 0;
    $ancestors = [];
    $has_ref = true;

    while ($has_ref) {
        $result = $wpdb->get_results("SELECT ref_id FROM user_ref WHERE user_id = $user_id");
        
        if ($result[0]->ref_id)
            $ancestors[] = $result[0]->ref_id;
        
        if (!$result[0]->ref_id) 
            $has_ref = false;
            
        $user_id = $result[0]->ref_id;
    }
    
    $res['user_id'] = $user_id;
    $res['user_refs'] = $ancestors;

    return $res;
}

function get_tron_transaction_by_hash($hash) {
    $url = "https://apilist.tronscanapi.com/api/transaction-info?hash=$hash";
    $headers = array(
        'Content-Type: application/json',
        //'Authorization: Bearer API_KEY'
    );
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // پردازش و نمایش پاسخ
    if (!$response)
        return false;
        
    $response = json_decode($response);
        
    return $response;
    
}

//// points
function get_user_point($user_id) {
    global $wpdb;
    
    $deposits = $wpdb->get_results("SELECT SUM(point) as 'sum' FROM user_points WHERE user_id = '$user_id' and type = 'deposit'");
    $deposits_sum = $deposits[0]->sum;
    
    $withdraws = $wpdb->get_results("SELECT SUM(point) as 'sum' FROM user_points WHERE user_id = '$user_id' and type = 'withdraw'");
    $withdraws_sum = $withdraws[0]->sum;

    return (int)$deposits_sum - (int)$withdraws_sum;
}

function get_point_value($points, $currency_symbol) {
    $currency_symbol = strtolower($currency_symbol);
    
    $point_usdt = $points / POINT_USDT;
    
    //اینجای به جای اینکه این همه از ایف استفاده کنیم، بجاش می تونیم از جدول currencies کوئری کنیم.
    if($currency_symbol == 'usdt')
        return $point_usdt;
    
    return 0;
    
}

function add_points($user_id, $point, $type, $extra="", $transaction_id=null) {
    global $wpdb; 
    
    $current_timestamp = time();
    $date_time_jalali = timestamp_to_jalali($current_timestamp + TIMEZONE_DIFF);
    
    $add = $wpdb->insert('user_points', array('user_id' => $user_id, 'point' => $point, 'type' => $type, 'transaction_id' => $transaction_id, 'date_time' => $current_timestamp, 'date_time_jalali' => $date_time_jalali, 'extra' => $extra, ));
    
    if (!$add) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        return $result_arr;
    }
    
    $result_arr['status'] = true; 
    $result_arr['result'] = $add->insert_id;
     
    return $result_arr;
}
//// End of points

// Accounts
function get_account_balance($account) {
    global $wpdb;
    
    $res = $wpdb->get_results("SELECT * FROM transactions WHERE from_account = '$account' OR to_account = '$account' ORDER BY id DESC LIMIT 1 ");
    
    if (!count($res))
        return 0; 
        
    $balance = $res[0]->from_account == $account ? $res[0]->balance_from : $res[0]->balance_to;
    
    return $balance;
}

function get_accounts_cronjobs() {
     global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM  accounts");
    
    if (!count($res))
        return false;
    
    return $res;
}

function get_accounts() {
     global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM  accounts");
    
    if (!count($res))
        return false;
        
    foreach($res as $account) {
        $account_balance = get_account_balance($account->account);
        $account->balance = $account_balance;
        $account->monthly_profit_percentage = get_account_monthly_profit_percentage($account->account, $account->currency, $account_balance);
    }
    
    return $res;
}

function create_user_bank_account($user_id, $account_length=12, $currency=DEFAULT_ACCOUNT_CURRENCY) {
    global $wpdb;
    
    do {
        $account = generateRandomNumber($account_length); 
        $res = $wpdb->get_results("SELECT * FROM  accounts WHERE account = '$account'");
    } while (count($res));
    
    $add = $wpdb->insert( 'accounts', array( 'account' => $account, 'currency' => $currency, 'user_id' => $user_id ) );
    
    if (!$add) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        return $result_arr;
    }
        
         
    $result_arr['status'] = true;
    $result_arr['result'] = $wpdb->insert_id;
    $result_arr['account'] = $account;
    
    return $result_arr;
    
}
 
function bank_account_exists($account) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM  accounts WHERE account = '$account'");
    
    if (!count($res))
        return false;
        
    return true;
}

function get_account_currency($account) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT currency FROM accounts WHERE account = '$account'");
    
    if(!count($res))
        return false;
        
    return $res[0]->currency;
}

function get_userbank_accounts($user_id) {
    global $wpdb;
    
    $res = $wpdb->get_results("SELECT * FROM accounts WHERE user_id = '$user_id'");
    
    if (!count($res))
        return false;
        
    foreach($res as $account) {
        $account->balance = get_account_balance($account->account);
    }
        
    return $res;
    
}

function owner_userid_bank_account($account) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT user_id FROM accounts WHERE account = '$account'");
    
    if (!count($res))
        return false;
         
    return $res[0]->user_id;
}
// End of accounts


// fixed functions
function get_month_days() {
    return 30;
}
function get_internal_transaction_fee($amount) {
    return 0;
}
function get_external_transaction_fee($amount) {
    return 0;
}
function get_account_monthly_profit_percentage($account, $account_currency=NULL, $balance=NULL) {
    if(!$balance)
        $balance = get_account_balance($account);
        
    if(!$account_currency)
        $account_currency = get_account_currency($account);
    else
        $account_currency = strtolower($account_currency);

    if ($account_currency == 'usdt') {
        return 10;
    } elseif ($account_currency == 'dai') {
        return 2;
    } else {
        return 0;
    }
    
}
// End of fixed functions

function is_tx_exists($tx, $network) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM transactions WHERE tx = '$tx' and network = '$network'");
    
    if (!count($res))
        return false;
        
    return $res[0];
}

function get_active_wallets($currency="usdt") {
    $currency = strtolower($currency);
    
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM crypto_wallets WHERE currency = '$currency' and active = 1");
    
    return $res;
}

function current_active_wallet_networks($currency = "usdt") {
    $currency = strtolower($currency);
     
    $current_active_wallets = get_active_wallets($currency);
    
    foreach($current_active_wallets as $wallet) {
        $res[] = $wallet->network;
    }
    
    return array_unique($res);
}

function is_wallet_exists($wallet) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM crypto_wallets WHERE address = '$wallet'");
    
    if (!count($res))
        return false;
        
    return $res[0];
    
}

function get_transaction($transaction_id) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM transactions WHERE id = $transaction_id");
    
    if (!count($res))
        return false;
        
    return $res[0];
    
}

function transaction_type($transaction_id, $account = null) {  // internal or external transaction
    $transaction = get_transaction($transaction_id);
    if(!$transaction)
        return false;
    
    if(!$transaction->from_account && $transaction->to_account) {
        $result['type'] = 'ex-deposit';
        $result['account'] = $transaction->to_account;
    } elseif(!$transaction->to_account && $transaction->from_account) {
        $result['type'] = 'ex-withdraw';
        $result['account'] = $transaction->from_account;
    } elseif($transaction->to_account && $transaction->from_account) {
        $result['type'] = 'in-transaction';
        $result['account']['from'] = $transaction->from_account;
        $result['account']['to'] = $transaction->to_account;
        if($account) {
            $in_transaction_type = $transaction->from_account == $account ? 'in-withdraw' : 'in-deposit';
            $result['inTransactionType'] = $in_transaction_type;
        }
    }
    
    return $result;
}

function add_internal_transaction($amount, $from_account, $to_account, $user_note="") {
    global $wpdb;
    
    // get current from and to balance
    if (in_array($from_account, EARNS))
        $current_balance_from = 0;
    else
    $current_balance_from = get_account_balance($from_account); 
    
    $current_balance_to = get_account_balance($to_account);
    
    // transaction fee
    if (in_array($from_account, EARNS))
        $internal_transaction_fee = 0;
    else
        $internal_transaction_fee = get_internal_transaction_fee($amount);
    
    // get new from and to balance
    if (in_array($from_account, EARNS))
        $new_balance_from = 0;
    else
        $new_balance_from = $current_balance_from - $amount - $internal_transaction_fee;
        
    $new_balance_to = $current_balance_to + $amount;
    
    // get time
    $current_timestamp = time();
    $date_time_jalali = timestamp_to_jalali($current_timestamp + TIMEZONE_DIFF); // 12600 معادل سه ساعت و نیم هست که بشود به وقت ایران
    
    // insert  to db
    $add = $wpdb->insert( 'transactions', array( 'amount' => $amount, 'from_account' => $from_account, 'to_account' => $to_account, 'fee' => $internal_transaction_fee, 'balance_from' => $new_balance_from, 'balance_to' => $new_balance_to, 'date_time' => $current_timestamp, 'date_time_jalali' => $date_time_jalali, 'user_note' => $user_note) );
    
    if (!$add) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        return $result_arr;
    }
     
    $result_arr['status'] = true; 
    $result_arr['result'] = $add->insert_id;
     
    return $result_arr;
}

function add_external_deposit_transaction($amount ,$to_account ,$tx ,$network , $from_wallet, $to_wallet ,$user_note) {
    global $wpdb;
    
    $current_balance_to = get_account_balance($to_account); 
    $new_balance_to = $current_balance_to + $amount;
    
    $current_timestamp = time();
    $date_time_jalali = timestamp_to_jalali($current_timestamp + TIMEZONE_DIFF);
    
    $add = $wpdb->insert( 'transactions', array( 'amount' => $amount, 'to_account' => $to_account, 'tx' => $tx, 'network' => $network, 'balance_to' => $new_balance_to, 'ex_deposit_from_wallet' => $from_wallet, 'ex_deposit_to_wallet' =>  $to_wallet, 'date_time' => $current_timestamp, 'date_time_jalali' => $date_time_jalali, 'user_note' => $user_note) );

    $pyramid_profit = false;
    // اینجا سود هرمی را را تقسیم کن بین کاربران => البته اگر فعال بود
    
    // اینجا قبل از هر چیزی می تونیم چک کنیم که اگر اولین تراکنشش بود، پوینت ها اضافه شوند.
    if(IS_PYRAMID_PROFIT_ACTIVE) {
        $user_refs = get_user_refs(owner_userid_bank_account($to_account))['user_refs'];
        
        foreach($user_refs as $index => $user_ref_id) { 
            $level = $index + 1;
            
            $level_distance_percentage = TOP_LEVEL_POINT;
            for($i=1; $i <= $level - 1; $i++) {
                $level_distance_percentage = ($level_distance_percentage * LEVEL_POINT_DISTANCE) / 100;
            }
            
            $point = ($amount * $level_distance_percentage) / 100;
            add_points($user_ref_id, $point, 'deposit', $extra="امتیاز بابت شارژ حساب شخص معرفی شده توسط شما");
        }
    }
    // اتمام: اینجا سود هرمی را را تقسیم کن بین کاربران

    if (!$add) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        
        return $result_arr;
    }
    
    $result_arr['status'] = true;
    $result_arr['result'] = true;
    $result_arr['pyramid_profit'] = $pyramid_profit;
    
    return $result_arr;
    
}

function admin_add_external_withdraw_transaction($external_withdraw_transactions_id, $tx, $from_wallet) {
    global $wpdb;
    
    $res = $wpdb->get_results("SELECT * FROM external_withdraw_transactions	where ID = '$external_withdraw_transactions_id'");
    
    if(!count($res)) { 
        $result['status'] = false;
        $result['result'] = 'transaction id is invalid';
        
        return $result;
    }

    $transaction = $res[0]; 
     
    $current_balance_from = get_account_balance($transaction->from_account); 
    
    $current_timestamp = time();
    $date_time_jalali = timestamp_to_jalali($current_timestamp + TIMEZONE_DIFF);
    
    if ($transaction->amount + $transaction->fee > $current_balance_from) {
        $update = $wpdb->update('external_withdraw_transactions', array('problem' => 1, 'admin_check_date_time' => $current_timestamp, 'extra' => 'insufficient balance'), array('id' => $external_withdraw_transactions_id));
        
        $result['status'] = false;
        $result['result'] = 'insufficient balance';
        
        return $result;
    } 
    
    $new_balance_from = $current_balance_from - $transaction->amount - $transaction->fee;
    
    
    $add = $wpdb->insert( 'transactions', array( 'amount' => $transaction->amount, 'from_account' => $transaction->from_account, 'tx' => $tx, 'ex_withdraw_from_wallet' => $from_wallet , 'ex_withdraw_to_wallet' => $transaction->ex_wallet, 'network' => $transaction->network, 'fee' => $transaction->fee, 'balance_from' => $new_balance_from, 'date_time' => $current_timestamp, 'date_time_jalali' => $date_time_jalali, 'user_note' => $transaction->user_note) );
    $update = $wpdb->update('external_withdraw_transactions', array('checkout' => 1, 'transaction_id' => $wpdb->insert_id, 'tx' => $tx, 'admin_check_date_time' => $current_timestamp), array('id' => $external_withdraw_transactions_id));
    
    if (!$add || !$update) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        
        return $result_arr;
    }
    
    $result_arr['status'] = true;
    $result_arr['result'] = true;
    
    return $result_arr;
}
function user_add_external_withdraw_transaction($user_id, $amount ,$from_account ,$ex_wallet, $network, $user_note="") {
    global $wpdb;
    
    $external_transaction_fee = get_external_transaction_fee($amount);
    
    $current_balance_from = get_account_balance($from_account);
        
    if($amount + $external_transaction_fee > $current_balance_from)
        return false;
    
    $new_balance_from = $current_balance_from - $amount - $external_transaction_fee;
    
    $current_timestamp = time();
    $date_time_jalali = timestamp_to_jalali($current_timestamp + TIMEZONE_DIFF); // 12600 معادل سه ساعت و نیم هست که بشود به وقت ایران
    
    $add = $wpdb->insert( 'external_withdraw_transactions', array( 'amount' => $amount, 'from_account' => $from_account, 'user_id' => $user_id, 'ex_wallet' => $ex_wallet, 'network' => $network, 'fee' => $external_transaction_fee, 'moment_balance_from' => $new_balance_from, 'date_time' => $current_timestamp, 'date_time_jalali' => $date_time_jalali, 'user_note' => $user_note) );

    
    if (!$add) {
        $result_arr['status'] = false;
        $result_arr['result'] = $wpdb->last_error;
        return $result_arr;
    }
    
    $result_arr['status'] = true;
    $result_arr['result'] = $add;
    
    return $result_arr;
}

function get_user_withdrawal_transactions($user_id) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM `external_withdraw_transactions` WHERE user_id = '$user_id' ORDER BY id DESC");
    
    if(!count($res))
        return false; 
        
    foreach($res as $transaction) {
        $transaction->account_currency = get_account_currency($transaction->from_account);
        
        //transaction status
        if (!$transaction->checkout)
            $transaction_status = 2;
        elseif ($transaction->checkout && $transaction->problem)
            $transaction_status = 0;
        elseif ($transaction->checkout && !$transaction->problem)
            $transaction_status = 1;
        
        $transaction->status = $transaction_status;
        
        // withdraw transaction id
        $transaction_proof_id = $transaction->transaction_id;
        if($transaction_proof_id) {
            $transaction_proof = $wpdb->get_results("SELECT * FROM `transactions` WHERE id = '$transaction_proof_id'");
            if(count($transaction_proof)) {
                $transaction->transaction_proof = $transaction_proof[0];
            }
        }
    }
        
    return $res;
    
}



////////////////////////////////////  wordpress attachments
function upload_to_wordpress_library($post_file_name = 'file') {
    extract($_POST);

    $file_name = $_FILES[$post_file_name]['name'];
    $file_temp = $_FILES[$post_file_name]['tmp_name'];

    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($file_temp);
    $filename = basename($file_name);
    $filetype = wp_check_filetype($file_name);
    $filename = time().'.'.$filetype['ext'];


    if (wp_mkdir_p($upload_dir['path'])) {
      $file = $upload_dir['path'] . '/' . $filename;
    } else {
      $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => sanitize_file_name($filename),
      'post_content' => '',
      'post_status' => 'inherit'
    ); 

    $attach_id = wp_insert_attachment($attachment, $file);

    if (!$attach_id)
      return false;

    require_once(ABSPATH . 'wp-admin/includes/image.php'); 
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
  }
////////////////////////////////////  wordpress attachments
 
function get_product_last_price($pid) {
    $regular_price = get_post_meta($pid, '_regular_price', true);
    $price = get_post_meta($pid, '_price', true);
    return !$price ? $regular_price : $price;
}

function ip_address_visited_page($ip, $page_id) {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM visit_page WHERE visitor_ip = '$ip' AND page_id = '$page_id'");
    if (!count($res))
        return false;
    
    return $res[0];
}

function get_text_from_web_page($url, $xpath) {
    $content = file_get_contents($url);

    $doc = new DOMDocument();
    @$doc->loadHTML($content);
    $doc = simplexml_import_dom($doc);
    $version = $doc->xpath($xpath);
    
    return $version[0][0];
}