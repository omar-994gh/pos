<?php
    function getClientMac(): string {
    if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
        exec('getmac', $out);
        return strtok($out[0], ' ');
    } else {
        // نفترض eth0؛ عدّل إذا لزم الأمر
        exec("cat /sys/class/net/eth0/address", $out);
        return trim($out[0]);
    }
}

echo getClientMac();
?>