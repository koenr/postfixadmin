<?php
//
// Postfix Admin
// by Mischa Peters <mischa at high5 dot net>
// Copyright (c) 2002 - 2005 High5!
// Licensed under GPL for more info check GPL-LICENSE.TXT
//
// File: edit-mailbox.php
//
// Template File: edit-mailbox.tpl
//
// Template Variables:
//
// tMessage
// tName
// tQuota
//
// Form POST \ GET Variables:
//
// fUsername
// fDomain
// fPassword
// fPassword2
// fName
// fQuota
// fActive
//

if (!isset($incpath)) $incpath = '.';

require ("$incpath/variables.inc.php");
require ("$incpath/config.inc.php");
require ("$incpath/functions.inc.php");
include ("$incpath/languages/" . check_language () . ".lang");

$SESSID_USERNAME = check_session ();

if (isset ($_GET['username'])) $fUsername = escape_string ($_GET['username']);
$fUsername = strtolower ($fUsername);
if (isset ($_GET['domain'])) $fDomain = escape_string ($_GET['domain']);

$pEdit_mailbox_name_text = $PALANG['pEdit_mailbox_name_text'];
$pEdit_mailbox_quota_text = $PALANG['pEdit_mailbox_quota_text'];

if ($_SERVER['REQUEST_METHOD'] == "GET")
{
   if (check_owner ($SESSID_USERNAME, $fDomain) || check_admin($SESSID_USERNAME))
   {
      $result = db_query ("SELECT * FROM $table_mailbox WHERE username='$fUsername' AND domain='$fDomain'");
      if ($result['rows'] == 1)
      {
         $row = db_array ($result['result']);
         $tName = $row['name'];
         $tQuota = divide_quota($row['quota']);
         $tActive = $row['active'];
         if ('pgsql'==$CONF['database_type']) {
            $tActive = ('t'==$row['active']) ? 1 : 0;
         }
      }

      $result = db_query ("SELECT * FROM $table_domain WHERE domain='$fDomain'");
      if ($result['rows'] == 1)
      {
			$row = db_array ($result['result']);
			$tMaxquota = $row['maxquota'];
      }
   }
   else
   {
      $tMessage = $PALANG['pEdit_mailbox_login_error'];
   }
}

if ($_SERVER['REQUEST_METHOD'] == "POST")
{
   if (isset ($_POST['fPassword'])) $fPassword = escape_string ($_POST['fPassword']);
   if (isset ($_POST['fPassword2'])) $fPassword2 = escape_string ($_POST['fPassword2']);
   if (isset ($_POST['fName'])) $fName = escape_string ($_POST['fName']);
   if (isset ($_POST['fQuota'])) $fQuota = intval ($_POST['fQuota']);
   if (isset ($_POST['fActive'])) $fActive = escape_string ($_POST['fActive']);

   if (! (check_owner ($SESSID_USERNAME, $fDomain) || check_admin($SESSID_USERNAME)) )
   {
      $error = 1;
      $tName = $fName;
      $tQuota = $fQuota;
      $tActive = $fActive;
      $tMessage = $PALANG['pEdit_mailbox_domain_error'] . "$fDomain</font>";
   }

   if ($fPassword != $fPassword2)
   {
	   $error = 1;
      $tName = $fName;
      $tQuota = $fQuota;
      $tActive = $fActive;
      $pEdit_mailbox_password_text = $PALANG['pEdit_mailbox_password_text_error'];
   }

   if ($CONF['quota'] == "YES")
   {
      if (!check_quota ($fQuota, $fDomain))
      {
         $error = 1;
         $tName = $fName;
         $tQuota = $fQuota;
         $tActive = $fActive;
         $pEdit_mailbox_quota_text = $PALANG['pEdit_mailbox_quota_text_error'];
      }
   }

   if ($error != 1)
   {
      if (!empty ($fQuota))
      {
         $quota = multiply_quota ($fQuota);
      }
      else
      {
         $quota = 0;
      }

      if ($fActive == "on")
      {
         $fActive = 1;
      }
      else
      {
         $fActive = 0;
      }
      $sqlActive=$fActive;
      if ('pgsql'==$CONF['database_type']) {
         $sqlActive=($fActive) ? 'true':'false';
      }

      if (empty ($fPassword) and empty ($fPassword2))
      {
         $result = db_query ("UPDATE $table_mailbox SET name='$fName',quota=$quota,modified=NOW(),active=$sqlActive WHERE username='$fUsername' AND domain='$fDomain'");
         if ($result['rows'] == 1) $result = db_query ("UPDATE $table_alias SET modified=NOW(),active='$sqlActive' WHERE address='$fUsername' AND domain='$fDomain'");
      }
      else
      {
         $password = pacrypt ($fPassword);
         $result = db_query ("UPDATE $table_mailbox SET password='$password',name='$fName',quota=$quota,modified=NOW(),active=$sqlActive WHERE username='$fUsername' AND domain='$fDomain'");
         if ($result['rows'] == 1) $result = db_query ("UPDATE $table_alias SET modified=NOW(),active='$sqlActive' WHERE address='$fUsername' AND domain='$fDomain'");
      }

      if ($result['rows'] != 1)
      {
         $tMessage = $PALANG['pEdit_mailbox_result_error'];
      }
      else
      {
         db_log ($SESSID_USERNAME, $fDomain, "edit mailbox", $fUsername);

         if (check_admin($SESSID_USERNAME)) {
            header ("Location: list-virtual.php?domain=$fDomain");
         } else {
            header ("Location: overview.php?domain=$fDomain");
         }
         exit;
      }
   }
}

include ("$incpath/templates/header.tpl");

if (check_admin($SESSID_USERNAME)) {
   include ("$incpath/templates/admin_menu.tpl");
} else {
   include ("$incpath/templates/menu.tpl");
}

include ("$incpath/templates/edit-mailbox.tpl");
include ("$incpath/templates/footer.tpl");
/* vim: set expandtab softtabstop=3 tabstop=3 shiftwidth=3: */
?>