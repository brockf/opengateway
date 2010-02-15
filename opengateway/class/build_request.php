<?php

require('opengateway.php');

$request = new OpenGateway();

$request->Authenticate('24RGP2W4DXPP7K5DXU25', 'IO1C08WRT3MRWNPJUI604JN862PYXCWB3U3C62OT', 'http://localhost/api/');
$request->SetMethod('charge');
$request->Param('customer_id', 1);
$request->Param('card_num', '4916634239086979', 'credit_card');
$request->Param('exp_month', 10, 'credit_card');
$request->Param('exp_year', 2011, 'credit_card');
echo $request->Process();