<?php
/**
 * Include required files.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require_once('../configure.php');

$pagename = basename($_SERVER['PHP_SELF']);

define('IS_JURY', false);
define('IS_PUBLIC', false);

require_once(LIBDIR . '/init.php');

setup_database_connection();

require_once(LIBWWWDIR . '/common.php');
require_once(LIBWWWDIR . '/print.php');
require_once(LIBWWWDIR . '/clarification.php');
require_once(LIBWWWDIR . '/scoreboard.php');
require_once(LIBWWWDIR . '/printing.php');
require_once(LIBWWWDIR . '/auth.php');

// The functions do_login and show_loginpage, if called, do not return.
if ( @$_POST['cmd']=='login' ) do_login();
if ( !logged_in() ) show_loginpage();

if ( !checkrole('team') ) {
	error("You do not have permission to perform that action (Missing role: 'team')");
}
if ( empty($teamdata) ) {
	error("You do not have a team associated with your account.  Please contact a staff member.");
}

if ( $teamdata['enabled'] != 1 ) {
	error("Team is not enabled.");
}

$cdata = getCurContest(TRUE);
$cid = (int)$cdata['cid'];

// Data to be sent as AJAX updates:
$updates = array(
	'clarifications' =>
	$DB->q('TABLE SELECT clarid, submittime, sender, recipient, probid, body
	        FROM team_unread
	        LEFT JOIN clarification ON(mesgid=clarid)
	        WHERE teamid = %i AND cid = %i', $teamid, $cid),
	'judgings' =>
	$DB->q('TABLE SELECT s.submitid, j.judgingid, j.result, s.submittime
	        FROM judging j
	        LEFT JOIN submission s USING(submitid)
	        WHERE s.teamid = %i AND j.cid = %i AND j.seen = 0
 	        AND j.valid=1 AND s.submittime < %i' .
	       ( dbconfig_get('verification_required', 0) ?
	         ' AND j.verified = 1' : ''), $teamid, $cid, $cdata['endtime']),
);
