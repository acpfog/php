<?php

# set default values for variables
$result = "";
$load = 0;

# short and long servers names
$servers = array(
    "data1" => "host1.server4you.net",
    "data2" => "host2.server4you.net",
    "data3" => "host3.server4you.net",
    "data4" => "host4.server4you.net",
    "data5" => "host5.server4you.net",
    "data6" => "host6.server4you.net"
);

# fast ethernet max speed in megabytes per second 
$max_bytes = 10485760.0;

foreach ($servers as $name => $server) {
# read data from rrd file using a long server name
    $rrd_data = rrd_fetch( "/var/lib/collectd/rrd/$server/interface-eth0/if_octets.rrd", array( "AVERAGE", "--resolution", "60", "--start", "end-1min" ) );
    $tx = array_values($rrd_data["data"]["tx"])[0]; 
# if data is absent set load to 1
    if (is_nan($tx)) {
        $load = 1;
    } else {
# calculate and round a server load
        $load = round( ( $tx / $max_bytes ) * 100 );
# normal load value sould be in range from 1 to 99 
        if ( $load <= 0 ) {
            $load = 1;
        } elseif ( $load > 99 ) {
            $load = 99;
        }
    }
# check if http service is alive
    $http = curl_init("http://$server");
    curl_setopt($http, CURLOPT_HEADER, true);
    curl_setopt($http, CURLOPT_NOBODY, true);
    curl_setopt($http, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($http, CURLOPT_TIMEOUT,10);
    $response = curl_exec($http);
    $httpcode = curl_getinfo($http, CURLINFO_HTTP_CODE);
    curl_close($http);
# if http service isn't alive set load to 100
    if ( $httpcode != "200" ) {
        $load = 100;
    }
# add a string to the result
    $result .= "$name:$load, ";

}
# print the result string
echo rtrim($result,", ");

?>
