<?php
// 
// Postfix Admin 
// by Mischa Peters <mischa at high5 dot net>
// Copyright (c) 2002 - 2005 High5!
// Licensed under GPL for more info check GPL-LICENSE.TXT
//
// File: backup.php
//
// Template File: -none-
//
// Template Variables:
//
// -none-
//
// Form POST \ GET Variables:
//
// -none-
//
require ("../config.inc.php");
require ("../functions.inc.php");
include ("../languages/" . check_language () . ".lang");

$SESSID_USERNAME = check_session ();
(!check_admin($SESSID_USERNAME) ? header("Location: " . $CONF['postfix_admin_url'] . "/main.php") && exit : '1');
(($CONF['backup'] == 'NO') ? header("Location: " . $CONF['postfix_admin_url'] . "/main.php") && exit : '1');

// TODO: make backup supported for postgres
if ('pgsql'==$CONF['database_type'])
{
    print '<p>Sorry: Backup is currently not supported for your DBMS.</p>';
}
/*
	SELECT attnum,attname,typname,atttypmod-4,attnotnull,atthasdef,adsrc
	AS def FROM pg_attribute,pg_class,pg_type,pg_attrdef
	WHERE pg_class.oid=attrelid AND pg_type.oid=atttypid
	AND attnum>0 AND pg_class.oid=adrelid AND adnum=attnum AND atthasdef='t' AND lower(relname)='admin'
	UNION SELECT attnum,attname,typname,atttypmod-4,attnotnull,atthasdef,''
	AS def FROM pg_attribute,pg_class,pg_type
	WHERE pg_class.oid=attrelid
	AND pg_type.oid=atttypid
	AND attnum>0
	AND atthasdef='f'
	AND lower(relname)='admin'
$db = $_GET['db'];
$cmd = "pg_dump -c -D -f /tix/miner/miner.sql -F p -N -U postgres $db";
$res = `$cmd`;
// Alternate: $res = shell_exec($cmd);
echo $res; 
*/

if ($_SERVER['REQUEST_METHOD'] == "GET")
{
   umask (077);
   $path = (ini_get('upload_tmp_dir') != '') ? ini_get('upload_tmp_dir') : '/tmp/';
   $filename = "postfixadmin-" . date ("Ymd") . "-" . getmypid() . ".sql";
   $backup = $path . $filename;

   $header = "#\n# Postfix Admin $version\n# Date: " . date ("D M j G:i:s T Y") . "\n#\n";

   if (!$fh = fopen ($backup, 'w'))
   {
      $tMessage = "<div class=\"error_msg\">Cannot open file ($backup)</div>";
      include ("../templates/header.tpl");
      include ("../templates/admin_menu.tpl");
      include ("../templates/message.tpl");
      include ("../templates/footer.tpl");
   } 
   else
   {
      fwrite ($fh, $header);
      
      $tables = array('admin','alias','domain','domain_admins','log','mailbox','vacation');

      for ($i = 0 ; $i < sizeof ($tables) ; ++$i)
      {
         $result = db_query ("SHOW CREATE TABLE ".table_by_pos($i));
         if ($result['rows'] > 0)
         {
            while ($row = db_array ($result['result']))
            {
               fwrite ($fh, "$row[1];\n\n");
            }
         }
      }   

      for ($i = 0 ; $i < sizeof ($tables) ; ++$i)
      {
         $result = db_query ("SELECT * FROM ".table_by_pos($i));
         if ($result['rows'] > 0)
         {
            while ($row = db_assoc ($result['result']))
            {
               foreach ($row as $key=>$val)
               {
                  $fields[] = $key;
                  $values[] = $val;
               }

               fwrite ($fh, "INSERT INTO ". $tables[$i] . " (". implode (',',$fields) . ") VALUES ('" . implode ('\',\'',$values) . "');\n");
               $fields = "";
               $values = "";
            }
         }
      }
   }
   header ("Content-Type: application/octet-stream");
   header ("Content-Disposition: attachment; filename=\"$filename\"");
   header ("Content-Transfer-Encoding: binary");
   header ("Content-Length: " . filesize("$backup"));
   header ("Content-Description: Postfix Admin");
   $download_backup = fopen ("$backup", "r");
   unlink ("$backup");
   fpassthru ($download_backup);
}
?>