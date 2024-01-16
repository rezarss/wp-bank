<?php

add_action('rest_api_init', 'get_posts_route');
function get_posts_route() {
  register_rest_route('bank', '/posts/', array(
    'methods' => 'GET',
    'callback' => function() {
        extract($_GET);
        extract($_POST);
        
        if (!($post_type && $numberposts && $orderby && $order))
            return new WP_Error('error', 'all parameters required', array('status' => 404));
        $posts =  get_posts(
            array( 
                'post_type'        => $post_type,
                'numberposts'      => $numberposts, 
        		'orderby'          => $orderby,
        		'order'            => $order,
            )    
        );
        
        switch($post_type) {
            case 'signal':
                foreach($posts as $post) {
                    $post->pair = get_post_meta($post->ID, 'signal_pair', true); 
                    $post->market = get_post_meta($post->ID, 'signal_market', true);
                    $post->future = get_post_meta($post->ID, 'signal_future_type', true);
                    $post->exchange = get_post_meta($post->ID, 'signal_exchange', true);
                    $post->other_exchange = get_post_meta($post->ID, 'signal_other_exchange', true);
                    $post->entry = get_post_meta($post->ID, 'signal_entry', true);
                    $post->stoploss = get_post_meta($post->ID, 'signal_stoploss', true);
                    $post->target1 = get_post_meta($post->ID, 'signal_target1', true);
                    $post->target2 = get_post_meta($post->ID, 'signal_target2', true);
                    $post->target3 = get_post_meta($post->ID, 'signal_target3', true);
                    $post->target4 = get_post_meta($post->ID, 'signal_target4', true);
                    $post->target5 = get_post_meta($post->ID, 'signal_target5', true);
                    
                    unset($post->post_password);
                }
                break;
            case 'news':
                foreach($posts as $post) {
                    $post->ticker = get_post_meta($post->ID, 'news_ticker', true);
                    
                    unset($post->post_password);
                }
                break;
            case 'analytic':
                foreach($posts as $post) {
                    $post->ticker = get_post_meta($post->ID, 'analytic_ticker', true);
                    
                    unset($post->post_password);
                }
                break;
            case 'event':
                foreach($posts as $post) { 
                    $post->event_type = get_post_meta($post->ID, 'event_type', true);
                    $post->event_other_type = get_post_meta($post->ID, 'event_other_type', true);
                    $post->ticker = get_post_meta($post->ID, 'event_ticker', true);
                    $post->ticker_symbol = get_post_meta($post->ID, 'event_ticker_symbol', true);
                    $post->event_datetime = get_post_meta($post->ID, 'event_datetime', true);
                    $post->real = get_post_meta($post->ID, 'event_real', true);
                    $post->fake = get_post_meta($post->ID, 'event_fake', true);
                    
                    unset($post->post_password);
                }
                break;
        } 
        
        print_r($posts);
    },
    'permission_callback' => 'public_api_controller',  
  ));
}

/////////////////////////////////////////////////////////////////////////////

add_action('rest_api_init', 'insert_post_route');
function insert_post_route() {
  register_rest_route('bank', '/insert-post/', array(
    'methods' => 'GET',
    'callback' => function() {
        extract($_GET);
        extract($_POST);
        
        //if (!($post_type && $numberposts && $orderby && $order))
            //return new WP_Error('error', 'all parameters required', array('status' => 404));
            
        // get category id 'all' of each custom post type: news, analytics, events and ...
        $category_ids = null;
        $category_taxonomy = null;
        $meta_input = null;
        switch($post_type) {
            case('signal'):
                $category_ids = 8;
                $category_taxonomy = 'signals-category';
                $meta_input = array(
                    'signal_pair'           => $pair,
                    'signal_market'         => $market,
                    'signal_future_type'    => $future_type,
                    'signal_exchange'       => $exchange,
                    'signal_other_exchange' => $other_exchange,
                    'signal_entry'          => $entry,
                    'signal_stoploss'       => $stoploss,
                    'signal_target1'        => $target1,
                    'signal_target2'        => $target2,
                    'signal_target3'        => $target3,
                    'signal_target4'        => $target4,
                    'signal_target5'        => $target5,
                );
                break;
            case('news'):
                $category_ids = 3;
                $category_taxonomy = 'news-category';
                $meta_input = array(
                    'news_ticker'           => $ticker,
                ); 
                break;
            case('analytic'):
                $category_ids = 6;
                $category_taxonomy = 'analytics-category';
                $meta_input = array(
                    'analytic_ticker'        => $ticker,
                );
                break;
            case('event'):
                $category_ids = 7;
                $category_taxonomy = 'events-category';
                $meta_input = array(
                    'event_type'            => $type,
                    'event_other_type'      => $other_type,
                    'event_ticker'          => $ticker,
                    'event_ticker_symbol'   => $ticker_symbol,
                    'event_datetime'        => $datetime,
                );
                break;
        }
            
        $post_id =  wp_insert_post(
            array( 
                'post_type'    => $post_type,
                'post_title'    => $post_title,
                'post_content'  => $post_content,
                'post_status'   => 'publish',
                'post_author'   => $author_user_id,
                'meta_input' => $meta_input,
            )    
        );
        // add to category
        $taxonomy_id = wp_set_object_terms( $post_id, array($category_ids), $category_taxonomy, false);
        
        return $post_id && $taxonomy_id ? $post_id : false;
    },
    'permission_callback' => 'public_api_controller',  
  ));
}

/////////////////////////////////////////////

add_action('rest_api_init', 'check_jwt_route');
function check_jwt_route() {
  register_rest_route('bank', '/check-jwt/', array(
    'methods' => 'POST', 
    'callback' => 'check_jwt_api', 
    'permission_callback' => 'public_api_controller',  
  ));
}

function check_jwt_api() { 
    extract($_POST);
    
    if (empty($jwt))
       return new WP_Error('error', 'unauthorized', array('status' => 401));
        
    return is_jwt_valid($jwt) ? true : new WP_Error('error', 'unauthorized', array('status' => 401));
        
}
/////////////////////////////////////////////

add_action('rest_api_init', 'generate_jwt_route');
function generate_jwt_route() {
  register_rest_route('bank', '/generate-jwt/', array(
    'methods' => 'GET', 
    'callback' => 'generate_jwt_api', 
    'permission_callback' => '__return_true',  
  ));
}

function generate_jwt_api() { 
    
    return generate_jwt(1);
        
}
/////////////////////////////////////////////

add_action('rest_api_init', 'otp_register_user_route');
function otp_register_user_route() {
  register_rest_route('bank', '/otp-register-user/', array(
    'methods' => 'POST', 
    'callback' => 'otp_register_user_func', 
    'permission_callback' => 'public_api_controller',  
  ));
}

function otp_register_user_func() {
    extract($_POST);
    
    if (!$otp || !$first_name || !$last_name || !$password || !$email)
        return new WP_Error('error', 'all parameters required', array('status' => 404));
        
    if (!verify_OTP($otp, $email))
        return new WP_Error('error', 'otp is wrong', array('status' => 401));
    
    $user = register_user($first_name, $last_name, $password, $email);
    
    if (!$user['status']) {
        return new WP_Error('error', $user['result'], array('status' => 400));
    }
        
    return generate_jwt($user['result']);
}
/////////////////////////////////////////////

add_action('rest_api_init', 'register_user_route');
function register_user_route() {
  register_rest_route('bank', '/register-user/', array(
    'methods' => 'POST', 
    'callback' => 'register_user_func', 
    'permission_callback' => 'public_api_controller',  
  ));
}

function register_user_func() {
    extract($_POST);
    if (!(($first_name && $last_name && $password && $country_code && $mobile && $email)))
        return new WP_Error('error', 'allParametersRequired', array('status' => 404));
        
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return new WP_Error('error', 'invalidEmailAddress', array('status' => 403));
    
    if ($country_code == '98') {
        if (strlen($mobile) < 10 || strlen($mobile) > 11)
            return new WP_Error('error', 'invalidMobile', array('status' => 403));
        
        if(!str_starts_with($mobile, '09'))
            if(!str_starts_with($mobile, '9'))
                return new WP_Error('error', 'invalidMobile', array('status' => 403));
    }
    
    if(strlen($password) < 6)
        return new WP_Error('error', 'invalidPassword', array('status' => 403));
    
    /*
    // strlen برای کاراکترهای فارسی داره 2 برابر حساب میکنه و باید خط پایین درست بشه
    if(strlen($first_name) < 2 || strlen($last_name) < 2)
        return new WP_Error('error', 'invalidFirstNameOrLastName', array('status' => 403));
    */  
    
    if(!$ref)
        $ref = 0;
        
    // register user
    $user = register_user($first_name, $last_name, $password, $country_code, $mobile, $email, $ref);
    
    if (!$user['status'])
        return new WP_Error('error', $user['result'], array('status' => 400)); 
        
    // create user bank account and add to db 'accounts'
    $user_bank_account = create_user_bank_account($user['result'], 12, DEFAULT_ACCOUNT_CURRENCY);
    
    if (!$user_bank_account['status']) {
        wp_delete_user( $user['result'] );
        return new WP_Error('error', 'CreateAccountServerError', array('status' => 400));
    }
        
    // if user created and bank account created then return
    $res['status'] = true; 
    $res['user_id'] = $user['result']; 
    $res['account'] = $user_bank_account['account']; 
    $res['jwt'] = generate_jwt($user['result']); 
    
    return $res;
}
/////////////////////////////////////////////

add_action('rest_api_init', 'user_exists_route'); 
function user_exists_route() { 
  register_rest_route('bank', '/user-exists/', array(
    'methods' => 'POST',
    'callback' => function() {
    extract($_POST);
        
    if (empty($user_login))
        return new WP_Error('error', 'loginNotFound', array('status' => 404));
    
    return is_user_exists($user_login) ? true : new WP_Error('error', 'userNotFount', array('status' => 404));
    
    },
    'permission_callback' => 'public_api_controller',
  ));
}

add_action('rest_api_init', 'change_user_forget_password_route'); 
function change_user_forget_password_route() { 
  register_rest_route('bank', '/change-user-forget-password/', array(
    'methods' => 'POST',
    'callback' => function() {
        extract($_POST);
        
        if(!$email)
            return new WP_Error('error', 'emailNotFount', array('status' => 404));
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return new WP_Error('error', 'emailWrong', array('status' => 403));
        
        if(!$otp)
            return new WP_Error('error', 'otpNotFount', array('status' => 404));
        
        if(!$password)
            return new WP_Error('error', 'passwordNotFount', array('status' => 404));
            
        $verify_otp = verify_otp($otp, $email);
        if(!$verify_otp)
            return new WP_Error('error', 'otpWrong', array('status' => 401));
        
        $user = get_user_by('email', $email);
        if (!$user)
            return new WP_Error('error', 'userNotFount', array('status' => 404));
        
        wp_set_password( $password, $user->ID );
        
        return true;

    }, 
    'permission_callback' => 'public_api_controller',
  ));
}

add_action('rest_api_init', 'change_user_passwprd_route'); 
function change_user_passwprd_route() { 
  register_rest_route('bank', '/change-user-password/', array(
    'methods' => 'POST',
    'callback' => function() {
        extract($_POST);
        
        if(!$new_password || !$old_password)
            return new WP_Error('error', 'passwordNotFount', array('status' => 404));
        
        $payload = get_jwt_payload($jwt);
        $user_id = $payload->user_id;
        
        $user = get_user_by('id', $user_id);
        
        if (!wp_check_password($old_password, $user->user_pass, $user_id))
            return new WP_Error('error', 'passwordNotMatch', array('status' => 403));
    
        wp_set_password( $new_password, $user_id );
        
        return true;

    }, 
    'permission_callback' => 'jwt_api_controller',
  ));
}

add_action('rest_api_init', 'user_profile_route'); 
function user_profile_route() { 
  register_rest_route('bank', '/user-profile/', array(
    'methods' => 'POST',
    'callback' => function() {
        extract($_POST);
        
        $payload = get_jwt_payload($jwt);
        $user_id = $payload->user_id;
    
        $user = get_user_by('id', $user_id);
        
        if (!$user)
            return new WP_Error('error', 'userNotFound', array('status' => 404));
            
        $user_arr['first_name'] = get_user_meta($user_id, 'first_name', true);
        $user_arr['last_name'] = get_user_meta($user_id, 'last_name', true);
        $user_arr['mobile'] = get_user_meta($user_id, 'digits_phone', true);
        $user_arr['email'] = $user->user_email;
            
        return $user_arr;
    }, 
    'permission_callback' => 'jwt_api_controller',
  ));
}


add_action('rest_api_init', 'login_with_password_route');
function login_with_password_route() { 
  register_rest_route('bank', '/login-with-password/', array(
    'methods' => 'POST',
    'callback' => 'login_with_password_func',
    'permission_callback' => 'public_api_controller',
  ));
}

function login_with_password_func() {
  session_start();
  extract($_POST);
  
  if (empty($login) || empty($password))
    return new WP_Error('error', 'loginOrPasswordNotFound', array('status' => 404));
    
  $user = is_user_exists($login); // check by userlogin, email and mobile: 98917...

  if (!$user)
    return new WP_Error('error', 'userNotFound', array('status' => 401));


  // check if password match
  if (wp_check_password($password, $user->user_pass, $user->ID)) {
    // If no error received, set the WP Cookie
    if (!is_wp_error($user)) {
      return generate_jwt($user->ID);
    } else {
      // echo "Failed to log in";
      return rest_ensure_response(false);
    }
  } else {
    return new WP_Error('error', 'passwordNotMatch', array('status' => 401));
  }
}
/////////////////////////////////////////////

add_action('rest_api_init', 'login_route');
function login_route() {
  register_rest_route('bank', '/login/', array(
    'methods' => 'POST',
    'callback' => 'login_user',
    'permission_callback' => '__return_true',
  ));
}

function login_user() {
  session_start();
  extract($_POST);
  
  if (empty($login) || empty($password))
    return new WP_Error('error', 'loginOrPasswordNotFound', array('status' => 404));
    
  $user = is_user_exists($login); // check by userlogin, email and mobile: 98917...

  if (!$user)
    return new WP_Error('error', 'userNotFound', array('status' => 404));


  // check if password match
  if (wp_check_password($password, $user->user_pass, $user->ID)) {
    // If no error received, set the WP Cookie
    if (!is_wp_error($user)) {
      return generate_jwt($user->ID);
    } else {
      // echo "Failed to log in";
      return rest_ensure_response(false);
    }
  } else {
    return new WP_Error('error', 'passwordNotMatch', array('status' => 404));
  }
}
/////////////////////////////////////////////

add_action('rest_api_init', 'login_with_mail_otp_route');
function login_with_mail_otp_route() {
  register_rest_route('bank', '/login-with-mail-otp/', array(
    'methods' => 'POST',
    'callback' => 'login_with_mail_otp',
    'permission_callback' => 'public_api_controller',
  ));
}
 
function login_with_mail_otp() { 
    extract($_POST); 

    if (empty($email))
        return new WP_Error('error', 'emailNotFound', array('status' => 404));
    
    if (empty($otp))
        return new WP_Error('error', 'otpNotFound', array('status' => 404));
        
    $user = is_user_exists($email);
    if (!$user)
        return new WP_Error('error', 'userNotFound', array('status' => 404));
        
    return verify_otp($otp, $email) ? generate_jwt($user->ID, JWT_SECRET) : new WP_Error('error', 'otpWrong', array('status' => 401));
    
}

/////////////////////////////////////////////
add_action('rest_api_init', 'verify_forget_password_otp_route');
function verify_forget_password_otp_route() {
  register_rest_route('bank', '/verify-forget-password-otp/', array(
    'methods' => 'POST',
    'callback' => 'verify_forget_password_otp',
    'permission_callback' => 'public_api_controller',
  ));
}
 
function verify_forget_password_otp() { 
    extract($_POST); 
    
    if (!$email || !$email == 'null')
        return new WP_Error('error', 'emailNotFound', array('status' => 404));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return new WP_Error('error', 'emailWrong', array('status' => 401));
    
    if (!$otp || $otp == 'null')
        return new WP_Error('error', 'otpNotFound', array('status' => 404));
        
    $user = is_user_exists($email);
    if (!$user)
        return new WP_Error('error', 'userNotFound', array('status' => 404));
        
    return verify_otp($otp, $email) ? true : new WP_Error('error', 'otpWrong', array('status' => 401));
    
}
/////////////////////////////////////////////

/////////////////////////////////////////////
add_action('rest_api_init', 'login_with_otp_route');
function login_with_otp_route() {
  register_rest_route('bank', '/login-with-otp/', array(
    'methods' => 'POST',
    'callback' => 'login_with_otp',
    'permission_callback' => '__return_true',
  ));
}

function login_with_otp() {
    extract($_POST);
    
    if (empty($mobile))
        return new WP_Error('error', 'mobile not found', array('status' => 404));
    
    if (empty($otp))
        return new WP_Error('error', 'otp not found', array('status' => 404));
        
    if (!is_user_exists($mobile))
        return new WP_Error('error', 'userNotFound', array('status' => 404));
    
    return verify_OTP($mobile, $otp) ? generate_jwt(1, JWT_SECRET) : new WP_Error('error', 'otp is wrong', array('status' => 401));
    
}

/////////////////////////////////////////////

add_action('rest_api_init', 'logout_user_route');
function logout_user_route() {
  register_rest_route('bank', '/logout-user/', array(
    'methods' => 'GET',
    'callback' => 'logout_user',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function logout_user() {
  wp_logout();

  session_start();
  $_SESSION['login'] = false;

  return rest_ensure_response(true);
}

/////////////////////////////////////////////

add_action('rest_api_init', 'login_status_route');
function login_status_route() {
  register_rest_route('bank', '/login-status/', array(
    'methods' => 'GET',
    'callback' => 'login_status',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function login_status() { 
  session_start();
  return rest_ensure_response($_SESSION['login']);
}

/////////////////////////////////////////////

add_action('rest_api_init', 'send_mail_otp_route');
function send_mail_otp_route() {
  register_rest_route('bank', '/send-mail-otp/', array(
    'methods' => 'POST',
    'callback' => 'send_mail_otp_func',
    'permission_callback' => 'public_api_controller',
  ));
}

function send_mail_otp_func() {
    extract($_POST);

    if (empty($email))
        return new WP_Error('error', 'emailNotFound', array('status' => 404));
        
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        return new WP_Error('error', 'emailIsInvalid', array('status' => 404));
        
    //if (!is_user_exists($email))
    //    return new WP_Error('error', 'emailNotRegistered', array('status' => 401));
        
    return send_mail_OTP($email, 5, 5 * 60);
}


/////////////////////////////////////////////
add_action('rest_api_init', 'send_forget_password_link_route');
function send_forget_password_link_route() {
  register_rest_route('bank', '/send-forget-password-link/', array(
    'methods' => 'POST',
    'callback' => 'send_forget_password_link',
    'permission_callback' => 'public_api_controller',
  ));
}

function send_forget_password_link() {
    extract($_POST);

    if (empty($email))
        return new WP_Error('error', 'emailNotFound', array('status' => 404));
        
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        return new WP_Error('error', 'emailIsInvalid', array('status' => 404));
        
    if (!is_user_exists($email))
        return new WP_Error('error', 'emailNotExists', array('status' => 401));
        
    $send_mail = send_forget_password_mail_OTP($email, 5, 10 * 60);
    
    if(!$send_mail)
        return new WP_Error('error', 'emailNotSent', array('status' => 401));
        
    return true;
}
/////////////////////////////////////////////

/////////////////////////////////////////////

add_action('rest_api_init', 'send_otp_route');
function send_otp_route() {
  register_rest_route('bank', '/send-otp/', array(
    'methods' => 'POST',
    'callback' => 'send_otp_func',
    'permission_callback' => '__return_true',
  ));
}

function send_otp_func() {
    extract($_POST);
    
    if (empty($mobile))
        return new WP_Error('error', 'mobileNotFound', array('status' => 404));
        
    if (!is_user_exists($mobile))
        return new WP_Error('error', 'mobileNotRegistered', array('status' => 401));
        
    if (empty($otpQtyNumber))
        return new WP_Error('error', 'otpQtyNumber not found', array('status' => 404));
    
    return send_OTP($mobile, $otpQtyNumber, 4 * 60);
}
/////////////////////////////////////////////

add_action('rest_api_init', 'verify_otp_route');
function verify_otp_route() {
  register_rest_route('bank', '/verify-otp/', array(
    'methods' => 'POST',
    'callback' => 'verify_otp_func',
    'permission_callback' => '__return_true',
  ));
}

function verify_otp_func() {
    extract($_POST);
    
    if (empty($mobile) || empty($otp))
        return new WP_Error('error', 'required', array('status' => 404));
    
    return verify_OTP($mobile, $otp);
}

/////////////////////////////////////////////

add_action('rest_api_init', 'get_user_route');
function get_user_route() {
  register_rest_route('bank', '/get-user/', array(
    'methods' => 'GET',
    'callback' => 'get_user',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_user() {
  return get_user_meta($_GET['userid'], 'usdt trc20', true);
  //return rest_ensure_response($_SESSION['login']);
}

/////////////////////////////////////////////


///////////////////////////////////////////// bank account

add_action('rest_api_init', 'get_active_wallets_by_account_route');
function get_active_wallets_by_account_route() {
  register_rest_route('bank', '/get-active-wallets-by-account/', array(
    'methods' => 'POST',
    'callback' => 'get_active_wallets_by_account_api',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_active_wallets_by_account_api() {
    extract($_POST);
    
    if (!$account)
        return new WP_Error('error', 'accountNotFound', array('status' => 404));
        
    $account_currency = get_account_currency($account);
    $active_wallets = get_active_wallets($account_currency);
    
    
    // get unique active networks
    $unique_networks = current_active_wallet_networks($account_currency);
    
    /*
    $arr = [];
    foreach($active_wallets as $wallet) {
        $network = $wallet->network;
        if(in_array($network, $arr))
            continue;
            
        $arr[] = $network;
    }
    */
    
    // results
    $result['currency'] = $account_currency;
    $result['wallets'] = $active_wallets;
    $result['uniqueNetworks'] = array_values($unique_networks);
    
    return $result;
}
    
    
add_action('rest_api_init', 'get_user_bank_accounts_route');
function get_user_bank_accounts_route() {
  register_rest_route('bank', '/get-user-bank-accounts/', array(
    'methods' => 'POST',
    'callback' => 'get_user_bank_accounts_api',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_user_bank_accounts_api() {
    extract($_POST);
    $payload = get_jwt_payload($jwt);
    
    $accounts = get_userbank_accounts($payload->user_id);
    
    if(!$accounts)
        return [];
        
    return $accounts;
}


add_action('rest_api_init', 'get_account_balance_route');
function get_account_balance_route() {
  register_rest_route('bank', '/account-balance/', array(
    'methods' => 'POST',
    'callback' => 'account_balance', 
    'permission_callback' => 'jwt_api_controller',
  ));
}

function account_balance() {
    extract($_POST);
    
    if (!$account)
        return new WP_Error('error', 'accountNotFound', array('status' => 404));
        
    if (!bank_account_exists($account))
        return new WP_Error('error', 'accountNotExists', array('status' => 404));
    
    $payload = get_jwt_payload($jwt); 

    if ($payload->user_id != owner_userid_bank_account($account))
        return new WP_Error('error', 'notAllowed', array('status' => 403));
        
    $res['balance'] = get_account_balance($account);
    $res['account'] = $account;
    $res['currency'] = get_account_currency($account);
    $res['currentTime'] = timestamp_to_jalali(time() + TIMEZONE_DIFF);
    
    return $res;
        
}


add_action('rest_api_init', 'get_account_transactions_route');
function get_account_transactions_route() {
  register_rest_route('bank', '/get-account-transactions/', array(
    'methods' => 'POST',
    'callback' => 'get_account_transactions',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_account_transactions() {
    extract($_POST); 
    
    if (!$page_number || !$limit)
        return new WP_Error('error', 'pageNumberOrLimitNotFound', array('status' => 404));
        
    if (!$account)
        return new WP_Error('error', 'accountNotFound', array('status' => 404));
        
    if (!bank_account_exists($account))
        return new WP_Error('error', 'accountNotExists', array('status' => 404));
        
    
    $payload = get_jwt_payload($jwt);

    if ($payload->user_id != owner_userid_bank_account($account))
        return new WP_Error('error', 'notAllowed', array('status' => 403));
        
    global $wpdb;
    $offset = ($page_number - 1) * 10;
    $res = $wpdb->get_results("SELECT * FROM transactions WHERE from_account = '$account' OR to_account = '$account' ORDER BY id DESC LIMIT $limit OFFSET $offset");
    
    if (!count($res))
        return [];
    
    foreach($res as $transaction) {
        // add transaction extra data
        $transaction_type = transaction_type($transaction->id, $account);
        $transaction->info = $transaction_type;
        
        // remove balance_from or balance_to
        if($account == $transaction->from_account)
            unset($transaction->balance_to);
        else
            unset($transaction->balance_from);
            
        // remove fee for deposit transactions
        if($transaction->from_account != 'point')
            if($transaction_type['type'] == 'ex-deposit' || $transaction_type['inTransactionType'] == 'in-deposit')
                unset($transaction->fee);
        
    }
    
    return $res;
}


add_action('rest_api_init', 'get_user_withdrawal_transactions_route');
function get_user_withdrawal_transactions_route() {
  register_rest_route('bank', '/get-user-withdrawal-transactions/', array(
    'methods' => 'POST',
    'callback' => 'get_user_withdrawal_transactions_api',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_user_withdrawal_transactions_api() {
    extract($_POST);
    $payload = get_jwt_payload($jwt);
    $user_id = $payload->user_id;
    
    $transactions = get_user_withdrawal_transactions($user_id);
    
    if(!$transactions)
        return [];
        
    return $transactions;
    
}


add_action('rest_api_init', 'internal_transaction_fee_route'); 
function internal_transaction_fee_route() {
  register_rest_route('bank', '/internal-transaction-fee/', array(
    'methods' => 'POST',
    'callback' => 'internal_transaction_fee',
    'permission_callback' => 'jwt_api_controller',
  ));
}
 
function internal_transaction_fee() {
    extract($_POST); 
    
    if (!$amount)
        return new WP_Error('error', 'amountNotFound', array('status' => 404));
    
    return get_internal_transaction_fee($amount);
}
    
    
add_action('rest_api_init', 'internal_transaction_route');
function internal_transaction_route() {
  register_rest_route('bank', '/internal-transaction/', array(
    'methods' => 'POST',
    'callback' => 'internal_transaction',
    'permission_callback' => 'jwt_api_controller',
  ));
}
 
function internal_transaction() {
    extract($_POST);
    
    if (!$from_account || !$to_account || !$amount)
        return new WP_Error('error', 'allParametersRequired', array('status' => 404));
        
    if (!is_numeric($amount))
        return new WP_Error('error', 'amountNotValid', array('status' => 403));
        
    // چک کند که هر دو شماره حساب درست و موجود باشد.
    if (!bank_account_exists($from_account))
        return new WP_Error('error', 'originInvalid', array('status' => 403));
        
    if (!bank_account_exists($to_account))
        return new WP_Error('error', 'destinationInvalid', array('status' => 403));
    
    // چک کند حساب فرستنده متعلق به خودش باشد
    $payload = get_jwt_payload($jwt);
    if ($payload->user_id != owner_userid_bank_account($from_account))
        return new WP_Error('error', 'notAllowed', array('status' => 403));
        
    // چک کند به خودش نفرستد
    if ($from_account == $to_account)
        return new WP_Error('error', 'sameOriginDestinationAccount', array('status' => 403));
        
    // چک کند که کارنسی هر دو حساب یکسان باشند
    if (get_account_currency($from_account) != get_account_currency($to_account))
        return new WP_Error('error', 'notSameCurrencyAccounts', array('status' => 403));
    
        
    // چک کند که حداقل مبلغ انتقال را رعایت می کند
    if ($amount < MIN_AMOUNT_IN_TRANSACTION)
        return new WP_Error('error', 'minAmmountError-'.MIN_AMOUNT_IN_TRANSACTION.'-'.get_account_currency($from_account), array('status' => 403));
        
        
    $internal_transaction_fee = get_internal_transaction_fee($amount);
        
    // اینجا چک کند که موجودی دارد یا خیر
    if ($amount + $internal_transaction_fee > get_account_balance($from_account))
        return new WP_Error('error', 'insifficentBalance', array('status' => 403));
        
    $transaction = add_internal_transaction($amount, $from_account, $to_account, $user_note);
    
    return $transaction;
        
}

add_action('rest_api_init', 'user_external_withdraw_transaction_route');
function user_external_withdraw_transaction_route() {
  register_rest_route('bank', '/user-external-withdraw-transaction/', array(
    'methods' => 'POST',
    'callback' => 'user_external_withdraw_transaction',
    'permission_callback' => 'jwt_api_controller',
  ));
}
  
function user_external_withdraw_transaction() {
    extract($_POST);
    
    if (!$from_account || $ex_wallet == "" | empty($ex_wallet) || !$amount || !$network)
        return new WP_Error('error', 'allParametersRequired', array('status' => 404));
        
    if (empty($user_note))
        $user_note = "";
        
    if(!is_numeric($amount)) 
        return new WP_Error('error', 'amountNotValid', array('status' => 403));
        
    if ( !in_array($network, current_active_wallet_networks(get_account_currency($from_account))) )
        return new WP_Error('error', 'invalidNetwork', array('status' => 403));
        
    // چک کند که حساب درست و موجود باشد.
    if (!bank_account_exists($from_account))
        return new WP_Error('error', 'OriginInvalid', array('status' => 403));
    
    // چک کند حساب فرستنده متعلق به خودش باشد
    $payload = get_jwt_payload($jwt);
    if ($payload->user_id != owner_userid_bank_account($from_account))
        return new WP_Error('error', 'notAllowed', array('status' => 403));
        
    // چک کند به خودش نفرستد
    if ($from_account == $ex_wallet)
        return new WP_Error('error', 'sameOriginDestinationAccount', array('status' => 403));
        
    // چک کند که حداقل مبلغ انتقال را رعایت می کند
    if ($amount < MIN_AMOUNT_EX_WITHDRAW_TRANSACTION)
        return new WP_Error('error', 'minAmmountError-'.MIN_AMOUNT_EX_WITHDRAW_TRANSACTION.'-'.get_account_currency($from_account), array('status' => 403));
        
    
        
    $external_transaction_fee = get_external_transaction_fee($amount);
        
    // اینجا چک کند که موجودی دارد یا خیر
    if ($amount + $external_transaction_fee > get_account_balance($from_account))
        return new WP_Error('error', 'insifficentBalance', array('status' => 403));
        
    $transaction = user_add_external_withdraw_transaction($payload->user_id, $amount ,$from_account ,$ex_wallet, $network ,$user_note);
    
    return $transaction;
        
}


add_action('rest_api_init', 'user_external_deposit_transaction_route');
function user_external_deposit_transaction_route() {
  register_rest_route('bank', '/user-external-deposit-transaction/', array(
    'methods' => 'POST',
    'callback' => 'user_external_deposit_transaction',
    'permission_callback' => 'jwt_api_controller',
  ));
}
 
function user_external_deposit_transaction() {
    extract($_POST);
    
    if (!$hash || $hash == "" || !$currency || !$network || !$to_account)
        return new WP_Error('error', 'allParametersRequired', array('status' => 404));
        
    // check if network is correct
    if ( !in_array($network, current_active_wallet_networks(get_account_currency($to_account))) )   // replaced with: if ($network != 'trc20')
        return new WP_Error('error', 'invalidTransactionNetwork', array('status' => 403));
        
    // get transaction
    if($network == 'trc20') {
        $transaction = get_tron_transaction_by_hash($hash);
        if (!$transaction->trc20TransferInfo)
            return new WP_Error('error', 'invalidTransactionHash', array('status' => 403)); 
            
        $transaction_symbol = strtolower($transaction->trc20TransferInfo[0]->symbol);
        $transaction_amount = $transaction->trc20TransferInfo[0]->amount_str / 1000000;
        $transaction_from_wallet = $transaction->trc20TransferInfo[0]->from_address; 
        $transaction_to_wallet = $transaction->trc20TransferInfo[0]->to_address;
    } else {
        return new WP_Error('error', 'networkNotSupportedAtThisTime', array('status' => 403));
    }
    
         
    // check if transaction already depositet or not
    $is_tx_already_deposited = is_tx_exists($hash, $network);
    if($is_tx_already_deposited)
        return new WP_Error('error', 'transactionAlreadyDeposited', array('status' => 403));
    
    // check if our wallet is correct => $to_wallet is our wallet
    $to_wallet = is_wallet_exists($transaction_to_wallet);
    if (!$to_wallet || $to_wallet->network != $network)
        return new WP_Error('error', 'invalidTransaction', array('status' => 403));
    
    // check if currency is correct
    if ($currency != $transaction_symbol)
        return new WP_Error('error', 'invalidTransactionSymbol', array('status' => 403));
        
    // check if account exists
    if (!bank_account_exists($to_account))
        return new WP_Error('error', 'invalidAccount', array('status' => 403));
        
    // check if account currency same as transaction symbol
    if (get_account_currency($to_account) != $currency)
        return new WP_Error('error', 'notSameCurrency', array('status' => 403));
        
    // check if user adds balance to his/her account
    $payload = get_jwt_payload($jwt);
    if ($payload->user_id != owner_userid_bank_account($to_account))
        return new WP_Error('error', 'notAllowed', array('status' => 403));
        
    // CHECK IF MIN DEPOSIT IS CORRECT
    if ($transaction_amount < MIN_AMOUNT_EX_DEPOSIT_TRANSACTION)
        return new WP_Error('error', 'minDepositAmmountError-'.MIN_AMOUNT_EX_DEPOSIT_TRANSACTION, array('status' => 403));
        
    $transaction = add_external_deposit_transaction($transaction_amount ,$to_account ,$hash ,$network , $transaction_from_wallet, $transaction_to_wallet ,$user_note);
    
    return $transaction;
    
}


////////// Points
add_action('rest_api_init', 'get_user_point_route');
function get_user_point_route() {
  register_rest_route('bank', '/get-user-point/', array(
    'methods' => 'POST',
    'callback' => 'get_user_point_api',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_user_point_api() {
    extract($_POST);
    $payload = get_jwt_payload($jwt); 
    $user_id = $payload->user_id;
    
    $point = get_user_point($user_id);
    
    $res['point'] = $point;
    $res['point_usdt'] = POINT_USDT;
    $res['usdt_value'] = $point / POINT_USDT;
    $res['currentTime'] = timestamp_to_jalali(time() + TIMEZONE_DIFF);
    
    
    return $res;
}

add_action('rest_api_init', 'get_user_points_route');
function get_user_points_route() {
  register_rest_route('bank', '/get-user-points/', array(
    'methods' => 'POST',
    'callback' => 'get_user_points',
    'permission_callback' => 'jwt_api_controller',
  ));
}

function get_user_points() {
    extract($_POST); 
    
    if (!$page_number || !$limit)
        return new WP_Error('error', 'pageNumberOrLimitNotFound', array('status' => 404));
    
    $payload = get_jwt_payload($jwt);
    $user_id = $payload->user_id;
    if (!get_user_by('id', $user_id))
        return new WP_Error('error', 'userNotFound', array('status' => 404));
        
    global $wpdb;
    $offset = ($page_number - 1) * 10;
    $res = $wpdb->get_results("SELECT * FROM user_points WHERE user_id = '$user_id' ORDER BY id DESC LIMIT $limit OFFSET $offset");
    
    if (!count($res))
        return [];
        
    foreach($res as $point_transaction) {
        // withdraw transaction id
        $transaction_proof_id = $point_transaction->transaction_id;
        if($transaction_proof_id) {
            $transaction_proof = $wpdb->get_results("SELECT * FROM `transactions` WHERE id = '$transaction_proof_id'");
            if(count($transaction_proof)) {
                $point_transaction->transaction_proof = $transaction_proof[0];
            }
        }
    }
    
    return $res;
}


add_action('rest_api_init', 'convert_points_route');
function convert_points_route() {
  register_rest_route('bank', '/convert-points/', array(
    'methods' => 'POST',
    'callback' => 'convert_points_api',
    'permission_callback' => 'jwt_api_controller',
  )); 
}

function convert_points_api() {
    extract($_POST);
    
    if (!$points || $points == 'null' || !$to_account)
        return new WP_Error('error', 'allParametersRequired', array('status' => 404));
        
    // get user total point
    $payload = get_jwt_payload($jwt);
    $user_id = $payload->user_id;
    
    if ($user_id != owner_userid_bank_account($to_account))  
        return new WP_Error('error', 'notAllowed', array('status' => 403)); 
    
    if (!is_numeric($points))
        return new WP_Error('error', 'pointsNotValid', array('status' => 403)); 
    
    if (is_numeric($points) && strpos($points, '.') !== false) // here it means points is float
        return new WP_Error('error', 'pointsNotInt', array('status' => 403)); 
    
    $user_total_point = get_user_point($user_id);
    
    // check if points is greater than user total point
    if ($points > $user_total_point)
        return new WP_Error('error', 'insufficientPoints', array('status' => 403));
    
    // check if user id exists
    if (!get_user_by('id', $user_id))
        return new WP_Error('error', 'userNotFound', array('status' => 404));
        
    // check if account exists
    if (!bank_account_exists($to_account))
        return new WP_Error('error', 'accountNotExists', array('status' => 403));
    
    // get account currency
    $account_currency = get_account_currency($to_account);
    
    $point_value = get_point_value($points, $account_currency);
    $amount = $point_value;
    
    $description = "اضافه شده به حساب $to_account بابت $points امتیاز";
    
    $add_internal_transaction = add_internal_transaction($amount, 'point', $to_account, $description); 
    $transaction_id = $add_internal_transaction['result'];
    
    if ($add_internal_transaction['status'])
        $add_points = add_points($user_id, $points, 'withdraw', $description, $transaction_id);
        
    if (!$add_points['status'])
        $wpdb->delete( 'transactions', array( 'id' => $transaction_id ) );
    
    return true;
}

////////// End of points

///////////////////////////////////////////// end of bank account

