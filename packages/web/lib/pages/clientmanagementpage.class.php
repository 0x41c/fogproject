<?php
class ClientManagementPage extends FOGPage {
    public $node = 'client';
    public function __construct($name = '') {
        $this->name = 'Client Management';
        parent::__construct($this->name);
    }
    public function index() {
        $this->title = _('FOG Client Installer');
        $curroot = trim(trim(self::getSetting('FOG_WEB_ROOT'),'/'));
        $webroot = sprintf('/%s',(strlen($curroot) > 1 ? sprintf('%s',$curroot) : ''));
        $url = filter_var(sprintf('http://%s%s/client/download.php',self::getSetting('FOG_WEB_HOST'),$webroot),FILTER_SANITIZE_URL);
        printf('<ul id="dashboard-boxes"><li><h4>%s</h4><div>%s<br/><br/><a href="%s?legclient">%s</a><br/><a href="%s?newclient">%s</a></div></li><li><h4>%s</h4><div>%s<br/><br/><a href="%s?fogcrypt">%s</a></div></li><li><h4>%s</h4><div>%s<br/><a href="%s?smartinstaller">%s</a></div></li></ul>',_('Client Service'),_('Download the FOG Client.  The client allows for advanced management of the PC, including hostname changing, domain joining, printer management, snapin deployments, and auto-reboot for tasking.'),$url,_('Legacy FOG Client (Not Recommended)'),$url,_('New FOG Client (Recommended)'),_('FOG Crypt'),_('FOG Crypt takes an entered password and encrypts it.  This encrypted password is what must be stored on the Legacy Password field.  It is ONLY required in this field if you are running the Legacy clients in your environment.'),$url,_('FOG Crypt'),_('Smart Installer'),_('Download the new clients 0.10.0 installer'),$url);
    }
}
