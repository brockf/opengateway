<?php

require('opengateway.php');

$request = new Recur();

$request->Authenticate('24RGP2W4DXPP7K5DXU25', 'IO1C08WRT3MRWNPJUI604JN862PYXCWB3U3C62OT', 'http://localhost/api/');
$request->Amount(24.99);
$request->CreditCard('David Ryan', '4024007155715823', 11, 2010, 123);
$request->Customer('David', 'Ryan', 'ABC Inc.', '1345 Quebec Street', '', 'Denver', 'CO', 'US', '80220', '3033319812', 'daveryan187@yahoo.com');
$request->UseGateway(20);
$request->UsePlan(5);
$request->Schedule(30, 12, 0, '2010-04-01');
echo $request->Charge(TRUE);