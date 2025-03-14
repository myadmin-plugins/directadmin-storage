<?php

use Detain\MyAdminDirectAdminWeb\HTTPSocket;

include_once __DIR__.'/../../../../include/functions.inc.php';

$db = get_module_db('backups');
if (count($_SERVER['argv']) < 2) {
    die("Call like {$_SERVER['argv'][0]} <hostname>\nwhere <hostname> is a backups server such as backups2004.interserver.net");
}
$db->query("select * from backup_masters where backup_name='".$db->real_escape($_SERVER['argv'][1])."'", __LINE__, __FILE__);
function_requirements('whm_api');
if ($db->num_rows() == 0) {
    die("Invalid Server {$_SERVER['argv'][1]} passed, did not match any backups server name");
}
$db->next_record(MYSQL_ASSOC);
echo "processing {$db->Record['backup_name']}\n";
$server_name = $db->Record['backup_ip'];;
$server_port = 2222;
$password = $db->Record['backup_key'];
$sock = new HTTPSocket();
$sock->connect("ssl://{$server_name}", $server_port);
//$sock->connect("http://{$server_name}", $server_port);
//$sock->connect('http://'.$server_name, $server_port);
$sock->set_login("admin", $password);

$sock->query('/CMD_API_SHOW_ALL_USERS');
$result = $sock->fetch_parsed_body();

print_r($result);
