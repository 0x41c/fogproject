<?php
class LDAPManager extends FOGManagerController {
    public function install($name) {
        $this->uninstall();
        $sql = "CREATE TABLE `LDAPServers`
            (`lsID` INTEGER NOT NULL AUTO_INCREMENT,
            `lsName` VARCHAR(250) NOT NULL,
            `lsDesc` longtext NOT NULL,
            `lsCreatedBy` VARCHAR(30) NOT NULL,
            `lsAddress` VARCHAR(30) NOT NULL,
            `lsCreatedTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `lsDN` VARCHAR(100) NOT NULL,
            `lsPort` INTEGER NOT NULL,
            PRIMARY KEY(`lsID`),
        KEY new_index (`lsName`))
        ENGINE = MyISAM";
        return static::$DB->query($sql)->fetch()->get();
    }
    public function uninstall() {
        return static::$DB->query("DROP TABLE IF EXISTS `LDAPServers`")->fetch()->get();
    }
}
