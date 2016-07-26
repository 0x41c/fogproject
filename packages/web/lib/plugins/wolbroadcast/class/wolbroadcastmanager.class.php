<?php
class WolbroadcastManager extends FOGManagerController {
    public function install($name) {
        $this->uninstall();
        $sql = "CREATE TABLE wolbroadcast
            (`wbID` INTEGER NOT NULL AUTO_INCREMENT,
            `wbName` VARCHAR(250) NOT NULL,
            `wbDesc` longtext NOT NULL,
            `wbBroadcast` VARCHAR(16) NOT NULL,
            PRIMARY KEY(`wbID`),
        INDEX new_index (`wbID`))
        ENGINE = MyISAM";
        return self::$DB->query($sql);
    }
    public function uninstall() {
        return self::$DB->query("DROP TABLE IF EXISTS `wolbroadcast`");
    }
}
