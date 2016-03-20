<?php
class Schema extends FOGController {
    protected $databaseTable = 'schemaVersion';
    protected $databaseFields = array(
        'id' => 'vID',
        'version' => 'vValue',
    );
    public function export_db($tables = false, $backup_name = false) {
        $mysqli = self::$DB->link();
        $mysqli->select_db(DATABASE_NAME);
        $mysqli->query("SET NAMES 'utf8'");
        $queryTables = $mysqli->query('SHOW TABLES');
        while ($row = $queryTables->fetch_row()) $target_tables[] = $row[0];
        if ($tables !== false) $target_tables = array_intersect($target_tables,$tables);
        ob_start();
        printf('-- FOG MySQL Dump created %s%s',$this->formatTime('','r'),"\n\n");
        if ($tables === false) {
            printf('DROP DATABASE IF EXISTS `%s`;%s',DATABASE_NAME,"\n\n");
            printf('CREATE DATABASE IF NOT EXISTS `%s`;%s',DATABASE_NAME,"\n\n");
        }
        printf('USE `%s`;%s',DATABASE_NAME,"\n\n");
        foreach ($target_tables AS $i => &$table) {
            $result = $mysqli->query("SELECT * FROM `$table`");
            $fields_amount = $result->field_count;
            $rows_num = $mysqli->affected_rows;
            $res = $mysqli->query("SHOW CREATE TABLE `$table`");
            $TableMLine = $res->fetch_row();
            echo "DROP TABLE IF EXISTS `$table`;";
            echo "\n\n{$TableMLine[1]};\n\n";
            for ($i=0,$st_counter=0;$i<$fields_amount;$i++,$st_counter=0) {
                while ($row = $result->fetch_row()) {
                    if ($st_counter % 100 == 0 || $st_counter == 0) echo "\nINSERT INTO `$table` VALUES";
                    echo "\n(";
                    for ($j=0;$j<$fields_amount;$j++) {
                        $row[$j] = str_replace("\n","\\n",addslashes($row[$j]));
                        if (isset($row[$j])) printf('"%s"',$row[$j]);
                        else echo '""';
                        if ($j < ($fields_amount - 1)) echo ',';
                    }
                    echo ')';
                    if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter+1 == $rows_num) echo ';';
                    else echo ',';
                    $st_counter++;
                }
                echo "\n\n\n";
            }
        }
        return ob_get_clean();
    }
    public function import_db($file) {
        $mysqli = self::$DB->link();
        if (false === ($fh = fopen($file,'rb'))) throw new Exception(_('Error Opening DB File'));
        while (($line = fgets($fh)) !== false) {
            if (substr($line,0,2) == '--' || $line == '') continue;
            $tmpline .= $line;
            if (substr(trim($line),-1,1) == ';') {
                if (false === $mysqli->query($tmpline)) $error .= _('Error performing query').'\'<strong>'.$line.'\': '.$mysqli->error.'</strong><br/><br/>';
                $tmpline = '';
            }
        }
        fclose($fh);
        if ($error) return $error;
        return true;
    }
    public function send_file($content, $backup_name = '') {
        $backup_name = $backup_name ? $backup_name : sprintf('fog_backup_%s.sql',$this->formatTime('','Ymd_His'));
        $filesize = strlen($content);
        header("X-Sendfile: $backup_name");
        header('Content-Description: File Transfer');
        header('Content-Type: text/plain');
        header("Content-Length: $filesize");
        header("Content-disposition: attachment; filename=$backup_name");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }
}
