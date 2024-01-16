<?php

//////////// api token
define('JWT_SECRET', 'bWKaNew4VxwLziLv');
define('SECURE_ENDPOINTS', ['bank']);
define('WHILE_LIST_ENDPOINTS', ['/bank/login', '/bank/validate-jwt']);

//////////// SMS
define("PUBLIC_API_KEY", "58o0DaozIsdfhh23zmSadfUNr");
define("IPPANEL_PATTERN", "djy4wcsa0lsd2hi");

//////////// TIME
define("TIMEZONE_DIFF", 12600);

//////////// email
define("EMAIL_FROM", 'noreply@rebex.ir'); 
define("EMAIL_FROM_NAME", 'CryptoGo'); 
define("EMAIL_HOST", '127.0.0.1'); 
define("EMAIL_USERNAME", 'noreply@rebex.ir'); 
define("EMAIL_PASSWORD", 'NF]g^qcN1G87'); 


//////////// BANK
define("BANK_DOOMAIN", 'https://rad.iran.liara.run/'); 

define("MIN_AMOUNT_IN_TRANSACTION", 1); 
define("MIN_AMOUNT_EX_DEPOSIT_TRANSACTION", 2);
define("MIN_AMOUNT_EX_WITHDRAW_TRANSACTION", 1); 
define("DEFAULT_ACCOUNT_CURRENCY", 'usdt'); 

// EARNS
define('EARNS', ['profit', 'point']);

// POINTS
define("POINT_USDT", 1); 

// Pyramid
define("IS_PYRAMID_PROFIT_ACTIVE", 1); 
define("TOP_LEVEL_POINT", 5); // 5 MEANS 5% OF AMOUNT
define("LEVEL_POINT_DISTANCE", 25); // 50 MEANS 50% OF IT'S TOP LEVEL. EX => LEVEL1=5%, LEVEL2=2.5%, LEVEL3=1.25%, LEVEL4=0.625% AND ...
