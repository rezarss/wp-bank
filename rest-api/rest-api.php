<?php

//////////////////////////////////////////////////////////////// controlling jwt

  //add_filter('rest_pre_echo_response', 
  function jwt_api_controller() {
    extract($_POST);
    extract($_GET);

    $current_rest_route = current_api_endpoint();
    //return array_key_exists($current_rest_route['full_path'], rest_get_server()->get_routes($current_rest_route['namespace'])) ? 'hast' : 'nist';


    if ((SECURE_ENDPOINTS === 'all' && !in_array($current_rest_route['full_path'], WHILE_LIST_ENDPOINTS)) || (in_array($current_rest_route['namespace'], SECURE_ENDPOINTS) && !in_array($current_rest_route['full_path'], WHILE_LIST_ENDPOINTS))) {
      /////// get token jwt
      if (!empty($jwt))
        $jwt = $jwt;
      elseif (!empty($_SERVER['HTTP_AUTHORIZATION']))
        $jwt = $_SERVER['HTTP_AUTHORIZATION'];
      else
        $jwt = '';
      /////// end of getting token jwt


      if (!$jwt)
        //return x_response_http('jwt missing', 401);
        return new WP_Error('error', 'jwt missing', array('status' => 401));

      if (!is_jwt_valid($jwt, JWT_SECRET))
        return new WP_Error('error', 'jwt is invalid', array('status' => 401));
        //return x_response_http('jwt is invalid', 401);
    }
    

    //* Return the new response 
    return true;
  }
  
  function public_api_controller() {
    extract($_POST);
    extract($_GET);
    
    /////// get token jwt
    if (!empty($public_api_key))
        $public_api_key = $public_api_key;
    elseif (!empty($_SERVER['HTTP_PUBLIC_API_KEY']))
        $public_api_key = $_SERVER['HTTP_PUBLIC_API_KEY'];
    else
        $public_api_key = '';
        
    // validate public api
    if (!$public_api_key)
        return new WP_Error('error', 'public api key missing', array('status' => 401));

    if ($public_api_key != PUBLIC_API_KEY)
        return new WP_Error('error', 'public api is invalid', array('status' => 401));
        
    return true;
    
  }
    //10, 3);
////////////////////////////////////////////////////////// end of controlling jwt

require_once('routes.php');