<?php
if(PHP_SAPI!=='cli') { http_response_code(403); exit; }
define('SHORTINIT', true);
require dirname(__DIR__).'/wp-load.php';
$file=fopen(dirname(__DIR__).'/db/wp_nhomf_nem.sql','wb');
if(!$file) exit(1);
fwrite($file,"-- WordPress NEM full database snapshot. Contains local site data.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
$tables=$wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($wpdb->prefix).'%'));
foreach($tables as $table) {
    $quoted='`'.str_replace('`','``',$table).'`';
    $create=$wpdb->get_row('SHOW CREATE TABLE '.$quoted, ARRAY_N);
    fwrite($file,"\nDROP TABLE IF EXISTS $quoted;\n".$create[1].";\n");
    for($offset=0;;$offset+=200) {
        $rows=$wpdb->get_results("SELECT * FROM $quoted LIMIT 200 OFFSET $offset",ARRAY_A);
        if(!$rows) break;
        foreach($rows as $row) {
            $values=array_map(function($value) use($wpdb) { return $value===null?'NULL':"'".mysqli_real_escape_string($wpdb->dbh,$value)."'"; },array_values($row));
            fwrite($file,"INSERT INTO $quoted VALUES (".implode(',',$values).");\n");
        }
    }
}
fwrite($file,"SET FOREIGN_KEY_CHECKS=1;\n"); fclose($file);
echo count($tables)." tables exported to db/wp_nhomf_nem.sql\n";
