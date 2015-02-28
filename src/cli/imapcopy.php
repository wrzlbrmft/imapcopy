<?php
if (2 > $argc) {
	printf("usage: php %s <confFile>\n", basename($argv[0]));
	die();
}
else {
	$confFile = $argv[1];

	if (file_exists($confFile)) {
		$conf = json_decode(file_get_contents($confFile), true);

		if (!is_array($conf) || !isset($conf['src']) || !isset($conf['dst'])) {
			printf("error: invalid/incomplete configuration");
			die();
		}
	}
	else {
		printf("error: configuration file not found (%s)\n", $confFile);
		die();
	}
}

print_r($conf);
