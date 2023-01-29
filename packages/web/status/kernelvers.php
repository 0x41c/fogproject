<?php
/**
 * Presents the FOG Kernels version that the clients will use.
 *
 * PHP version 5
 *
 * @category KernelVersion
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Presents the FOG Kernels version that the clients will use.
 *
 * @category KernelVersion
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
require '../commons/base.inc.php';
session_write_close();
ignore_user_abort(true);
set_time_limit(0);
header('Content-Type: text/event-stream');

if (isset($_POST['url'])) {

    if (is_null($currentUser))
        goto unauthorized;

    // Prevent an unauthenticated user from making arbitrary requests.
    $unauthorized = !$currentUser->isValid() || empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest';

    if ($unauthorized) {
        unauthorized:
        echo _('Unauthorized');
        exit;
    }

    $res = $FOGURLRequests
        ->process(filter_input(INPUT_POST, 'url'));
    foreach ((array) $res as &$response) {
        echo $response;
        unset($response);
    }

    exit;
}

$kernelvers = function ($kernel) {
    $currpath = sprintf(
        '%s%sservice%sipxe%s%s',
        BASEPATH,
        DS,
        DS,
        DS,
        $kernel
    );
    $basepath = escapeshellarg($currpath);
    $findstr = sprintf(
        'strings %s | grep -A1 "%s:" | tail -1 | awk \'{print $1}\'',
        $basepath,
        'Undefined video mode number'
    );
    return shell_exec($findstr);
};
printf(
    "%s\n",
    FOG_VERSION
);
printf(
    "bzImage Version: %s\n",
        $kernelvers('bzImage')
);
printf(
    "bzImage32 Version: %s",
        $kernelvers('bzImage32')
);