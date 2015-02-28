<?php
require_once('../lib/classes/Imap.php');
require_once('../lib/utils.php');

if (2 > $argc) {
	printf("usage: php %s <confFile>\n", basename($argv[0]));
	die();
}
else {
	$confFile = $argv[1];

	if (file_exists($confFile)) {
		$conf = json_decode(file_get_contents($confFile), true);

		if (!is_array($conf) || !isset($conf['src']) || !isset($conf['dst'])) {
			printf("error: invalid/incomplete configuration\n");
			die();
		}
	}
	else {
		printf("error: configuration file not found (%s)\n", $confFile);
		die();
	}
}

printf("*** opening source\n");
$src = new Imap($conf['src']);
printf('    connecting via %s...', $src->getMailbox());
$_ = $src->connect();
printf(" %s\n", test($_));
if (!$src->isConnected()) {
	printf(">>> source is not ready\n");
	die();
}
else {
	printf(">>> source is ready\n");
}

printf("\n");

printf("*** opening destination\n");
$dst = new Imap($conf['dst']);
printf('    connecting via %s...', $dst->getMailbox());
$_ = $dst->connect();
printf(" %s\n", test($_));
if (!$dst->isConnected()) {
	printf(">>> destination is not ready\n");
	die();
}
else {
	printf(">>> destination is ready\n");
}
