<?php

$servers = array(
    "data1" => "host1.server4you.net",
    "data2" => "host2.server4you.net",
    "data3" => "host3.server4you.net",
    "data4" => "host4.server4you.net",
    "data5" => "host5.server4you.net",
    "data6" => "host6.server4you.net"
);

$load = 0;
$max_bytes = 10485760.0;
$result = "";

foreach ($servers as $name => $server) {

    $rrd_data = rrd_fetch( "/var/lib/collectd/rrd/$server/interface-eth0/if_octets.rrd", array( "AVERAGE", "--resolution", "60", "--start", "end-1min" ) );
    $tx = array_values($rrd_data["data"]["tx"])[0]; 
    if (is_nan($tx)) {
        $load = 1;
    } else {
        $load = round( ( $tx / $max_bytes ) * 100 );
        if ( $load <= 0 ) {
            $load = 1;
        } elseif ( $load > 99 ) {
            $load = 99;
        }
    }

    $http = curl_init("http://$server");
    curl_setopt($http, CURLOPT_HEADER, true);
    curl_setopt($http, CURLOPT_NOBODY, true);
    curl_setopt($http, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($http, CURLOPT_TIMEOUT,10);
    $response = curl_exec($http);
    $httpcode = curl_getinfo($http, CURLINFO_HTTP_CODE);
    curl_close($http);

    if ( $httpcode != "200" ) {
        $load = 100;
    }

    $result .= "$name:$load, ";

}

echo rtrim($result,", ");

?>
