<?php
class PingHosts extends FOGService {
    public $dev = '';
    public $log = '';
    public $zzz = '';
    public $sleeptime = 'PINGHOSTSLEEPTIME';
    public function __construct() {
        parent::__construct();
        $this->log = sprintf('%s%s',$this->logpath,$this->getSetting('PINGHOSTLOGFILENAME'));
        $this->dev = $this->getSetting('PINGHOSTDEVICEOUTPUT');
        $this->zzz = (int)$this->getSetting($this->sleeptime);
    }
    private function commonOutput() {
        try {
            if (!$this->getSetting('FOG_HOST_LOOKUP')) throw new Exception(_(' * Host Ping is not enabled'));
            $webServerIP = self::$FOGCore->resolveHostName($this->getSetting('FOG_WEB_HOST'));
            $this->outall(sprintf(' * FOG Web Host IP: %s',$webServerIP));
            $this->getIPAddress();
            foreach ((array)self::$ips AS $i => &$ip) {
                if (!$i) $this->outall(" * This server's IP Addresses");
                $this->outall(" |\t$ip");
                unset($ip);
            }
            if (!in_array($webServerIP,self::$ips)) throw new Exception(_('I am not the fog web server'));
            $hostCount = self::getClass('HostManager')->count();
            $this->outall(sprintf(' * %s %s %s%s',_('Attempting to ping'),self::getClass('HostManager')->count(),_('host'),($hostCount != 1 ? 's' : '')));
            foreach ((array)self::getClass('HostManager')->find() AS $i => &$Host) {
                if (!$Host->isValid()) continue;
                $Host->setPingStatus();
                unset($Host);
            }
            $this->outall(' * All status\' have been updated');
        } catch (Exception $e) {
            $this->outall($e->getMessage());
        }
    }
    public function serviceRun() {
        $this->out(' ',$this->dev);
        $this->out(' +---------------------------------------------------------',$this->dev);
        $this->commonOutput();
        $this->out(' +---------------------------------------------------------',$this->dev);
    }
}
