<?php
class FOGConfigurationPage extends FOGPage {
    public $node = 'about';
    public function __construct($name = '') {
        $this->name = 'FOG Configuration';
        parent::__construct($this->name);
        $this->menu = array(
            'license'=>self::$foglang['License'],
            'kernel-update'=>self::$foglang['KernelUpdate'],
            'pxemenu'=>self::$foglang['PXEBootMenu'],
            'customize-edit'=>self::$foglang['PXEConfiguration'],
            'new-menu'=>self::$foglang['NewMenu'],
            'client-updater'=>self::$foglang['ClientUpdater'],
            'mac-list'=>self::$foglang['MACAddrList'],
            'settings'=>self::$foglang['FOGSettings'],
            'logviewer'=>self::$foglang['LogViewer'],
            'config'=>self::$foglang['ConfigSave'],
            'http://www.sf.net/projects/freeghost'=>self::$foglang['FOGSFPage'],
            'https://fogproject.org'=>self::$foglang['FOGWebPage'],
            'https://github.com/fogproject/fogproject.git'=>_('FOG Project on Github'),
            'https://github.com/fogproject/fog-client.git'=>_('FOG Client on Github'),
            'https://wiki.fogproject.org/wiki/index.php'=>_('FOG Wiki'),
            'https://forums.fogproject.org'=>_('FOG Forums'),
            'https://www.paypal.com/us/cgi-bin/webscr?cmd=_flow&SESSION=kXqYEYWlhYQzPiGFLuwJpkwxqhk940AnPXR--PNpYY4IS2WDicAFGopYjOG&dispatch=5885d80a13c0db1f8e263663d3faee8d6625bf1e8bd269586d425cc639e26c6a'=>_('Donate to FOG'),
        );
        self::$HookManager->processEvent('SUB_MENULINK_DATA',array('menu'=>&$this->menu,'submenu'=>&$this->subMenu,'id'=>&$this->id,'notes'=>&$this->notes));
    }
    public function index() {
        $this->version();
    }
    public function version() {
        $URLs = array();
        $Names = array();
        $this->title = _('FOG Version Information');
        printf('<p>%s: %s</p>',_('Version'),FOG_VERSION);
        $URLs[] = sprintf('https://fogproject.org/version/index.php?version=%s',FOG_VERSION);
        $Nodes = (array)self::getClass('StorageNodeManager')->find(array('isEnabled'=>1));
        array_map(function(&$StorageNode) use (&$URLs) {
            if (!$StorageNode->isValid()) return;
            $curroot = trim(trim($StorageNode->get('webroot'),'/'));
            $webroot = sprintf('/%s',(strlen($curroot) > 1 ? sprintf('%s/',$curroot) : ''));
            $URLs[] = filter_var(sprintf('http://%s%sstatus/kernelvers.php',$StorageNode->get('ip'),$webroot),FILTER_SANITIZE_URL);
            unset($StorageNode);
        },$Nodes);
        array_unshift($Nodes,'');
        $Responses = self::$FOGURLRequests->process($URLs,'GET');
        array_walk($Responses,function(&$data,&$i) use ($Nodes) {
            if (!$i) printf('<p><div class="sub">%s</div></p><h1>Kernel Versions</h1>',$data);
            else printf('<h2>%s</h2><pre>%s</pre>',$Nodes[$i],$data);
            unset($data,$i);
        });
        unset($Responses,$Nodes);
    }
    public function license() {
        $this->title = _('FOG License Information');
        $file = "./languages/{$_SESSION['locale']}/gpl-3.0.txt";
        if (($fh = fopen($file,'rb')) === false) return;
        echo '<pre>';
        while (($line = fgets($fh)) !== false) echo $line;
        echo '</pre>';
        fclose($fh);
    }
    public function kernel() {
        $this->kernel_update_post();
    }
    public function kernel_update() {
        $this->kernelselForm('pk');
        $url = filter_var(sprintf('https://fogproject.org/kernels/kernelupdate.php?version=%s',FOG_VERSION),FILTER_SANITIZE_URL);
        $htmlData = self::$FOGURLRequests->process($url,'GET');
        echo $htmlData[0];
    }
    public function kernelselForm($type) {
        printf('<div class="hostgroup">%s</div><div><form method="post" action="%s"><select name="kernelsel" onchange="this.form.submit()"><option value="pk" %s>%s</option><option value="ok" %s>%s</option></select></form></div>',_('This section allows you to update the Linux kernel which is used to boot the client computers.  In FOG, this kernel holds all the drivers for the client computer, so if you are unable to boot a client you may wish to update to a newer kernel which may have more drivers built in.  This installation process may take a few minutes, as FOG will attempt to go out to the internet to get the requested Kernel, so if it seems like the process is hanging please be patient.'),$this->formAction,($type == 'pk' ? 'selected' : ''),_('Published Kernel'),($type == 'ok' ? 'selected' : ''),_('Old Published Kernels'));
    }
    public function kernel_update_post() {
        if (in_array($_REQUEST['sub'],array('kernel-update','kernel_update'))) {
            switch ($_REQUEST['kernelsel']) {
            case 'pk':
                $this->kernelselForm('pk');
                $url = filter_var(sprintf('https://fogproject.org/kernels/kernelupdate.php?version=%s',FOG_VERSION),FILTER_SANITIZE_URL);
                $htmlData = self::$FOGURLRequests->process($url,'GET');
                echo $htmlData[0];
                break;
            case 'ok':
                $this->kernelselForm('ok');
                $url = filter_var(sprintf('https://freeghost.sourceforge.net/kernelupdates/index.php?version=%s',FOG_VERSION),FILTER_SANITIZE_URL);
                $htmlData = self::$FOGURLRequests->process($url,'GET');
                echo $htmlData[0];
                break;
            default:
                $this->kernelselForm('pk');
                $url = filter_var(sprintf('https://fogproject.org/kernels/kernelupdate.php?version=%s',FOG_VERSION),FILTER_SANITIZE_URL);
                $htmlData = self::$FOGURLRequests->process($url,'GET');
                echo $htmlData[0];
                break;
            }
        } else if ($_REQUEST['install']) {
            $_SESSION['allow_ajax_kdl'] = true;
            $_SESSION['dest-kernel-file'] = trim(basename($_REQUEST['dstName']));
            $_SESSION['tmp-kernel-file'] = sprintf('%s%s%s%s',DIRECTORY_SEPARATOR,trim(sys_get_temp_dir(),DIRECTORY_SEPARATOR),DIRECTORY_SEPARATOR,basename($_SESSION['dest-kernel-file']));
            $_SESSION['dl-kernel-file'] = base64_decode($_REQUEST['file']);
            if (file_exists($_SESSION['tmp-kernel-file'])) @unlink($_SESSION['tmp-kernel-file']);
            printf('<div id="kdlRes"><p id="currentdlstate">%s</p><i id="img" class="fa fa-cog fa-2x fa-spin"></i></div>',_('Starting process...'));
        } else {
            $tmpFile = basename(htmlentities($_REQUEST['file'],ENT_QUOTES,'utf-8'));
            $tmpArch = htmlentities($_REQUEST['arch'],ENT_QUOTES,'utf-8');
            printf('<form method="post" action="?node=%s&sub=kernel&install=1&file=%s"><p>%s: <input class="smaller" type="text" name="dstName" value="%s"/></p><p><input class="smaller" type="submit" value="%s"/></p></form>',$this->node,basename(htmlentities($_REQUEST['file'],ENT_QUOTES,'utf-8')),_('Kernel Name'),($tmpArch == 64 || ! $tmpArch ? 'bzImage' : 'bzImage32'),_('Next'));
        }
    }
    public function pxemenu() {
        $this->title = _('FOG PXE Boot Menu Configuration');
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $noMenu = self::getSetting('FOG_NO_MENU') ? ' checked' : '';
        $hidChecked = (self::getSetting('FOG_PXE_MENU_HIDDEN') ? ' checked' : '');
        $hideTimeout = self::getSetting('FOG_PXE_HIDDENMENU_TIMEOUT');
        $timeout = self::getSetting('FOG_PXE_MENU_TIMEOUT');
        $bootKeys = self::getClass('KeySequenceManager')->buildSelectBox(self::getSetting('FOG_KEY_SEQUENCE'));
        $advLogin = (self::getSetting('FOG_ADVANCED_MENU_LOGIN') ? ' checked' : '');
        $advanced = self::getSetting('FOG_PXE_ADVANCED');
        $exitNorm = Service::buildExitSelector('bootTypeExit',self::getSetting('FOG_BOOT_EXIT_TYPE'));
        $exitEfi = Service::buildExitSelector('efiBootTypeExit',self::getSetting('FOG_EFI_BOOT_EXIT_TYPE'));
        $fields = array(
            _('No Menu') => sprintf('<input type="checkbox" name="nomenu" value="1"%s/><i class="icon fa fa-question hand" title="%s"></i>',$noMenu,_('Option sets if there will even be the presence of a menu to the client systems. If there is not a task set, it boots to the first device, if there is a task, it performs that task.')),
            _('Hide Menu') => sprintf('<input type="checkbox" name="hidemenu" value="1"%s/><i class="icon fa fa-question hand" title="%s"></i>',$hidChecked,_('Option below sets the key sequence. If none is specified, ESC is defaulted. Login with the FOG Credentials and you will see the menu. Otherwise it will just boot like normal.')),
            _('Hide Menu Timeout') => sprintf('<input type="text" name="hidetimeout" value="%s"/><i class="icon fa fa-question hand" title="%s"></i>',$hideTimeout,_('Option specifies the timeout value for the hidden menu system')),
            _('Advanced Menu Login') => sprintf('<input type="checkbox" name="advmenulogin" value="1"%s/><i class="icon fa fa-question hand" title="%s"></i>',$advLogin,_('Option below enforces a Login system for the Advanced menu parameters. If off, no login will appear, if on, it will ony allow login to the advanced system.')),
            _('Boot Key Sequence') => $bootKeys,
            sprintf('%s:*',_('Menu Timeout (in seconds)')) => sprintf('<input type="text" name="timeout" value="%s" id="timeout"/>',$timeout),
            _('Exit to Hard Drive Type') => $exitNorm,
            _('Exit to Hard Drive Type(EFI)') => $exitEfi,
            '<a href="#" onload="$(\'#advancedTextArea\').hide();" onclick="$(\'#advancedTextArea\').toggle();" id="pxeAdvancedLink">Advanced Configuration Options</a>' => sprintf('<div id="advancedTextArea" class="hidden"><div class="lighterText tabbed">%s</div><textarea rows="5" cols="40" name="adv">%s</textarea></div>',_('Add any custom text you would like included added as a part of your <i>default</i> file.'),$advanced),
            '&nbsp;' => sprintf('<input type="submit" value="%s"/>',_('Save PXE MENU')),
        );
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager->processEvent('PXE_BOOT_MENU',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        echo '</form>';
    }
    public function pxemenu_post() {
        try {
            $timeout = trim($_REQUEST['timeout']);
            $timeout = (is_numeric($timeout) || (int) $timeout >= 0 ? true : false);
            if (!$timeout) throw new Exception(_('Invalid Timeout Value'));
            else $timeout = trim($_REQUEST['timeout']);
            $hidetimeout = trim($_REQUEST['hidetimeout']);
            $hidetimeout = (is_numeric($hidetimeout) || (int) $hidetimeout >= 0 ? true : false);
            if (!$hidetimeout) throw new Exception(_('Invalid Timeout Value'));
            else $hidetimeout = trim($_REQUEST['hidetimeout']);
            if (!$this
                ->setSetting('FOG_PXE_MENU_HIDDEN',$_REQUEST['hidemenu'])
                ->setSetting('FOG_PXE_MENU_TIMEOUT',$timeout)
                ->setSetting('FOG_PXE_ADVANCED',$_REQUEST['adv'])
                ->setSetting('FOG_KEY_SEQUENCE',$_REQUEST['keysequence'])
                ->setSetting('FOG_NO_MENU',$_REQUEST['nomenu'])
                ->setSetting('FOG_BOOT_EXIT_TYPE',$_REQUEST['bootTypeExit'])
                ->setSetting('FOG_EFI_BOOT_EXIT_TYPE',$_REQUEST['efiBootTypeExit'])
                ->setSetting('FOG_ADVANCED_MENU_LOGIN',$_REQUEST['advmenulogin'])
                ->setSetting('FOG_PXE_HIDDENMENU_TIMEOUT',$hidetimeout)) throw new Exception(_('PXE Menu update failed'));
            throw new Exception(_('PXE Menu has been updated'));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function customize_edit() {
        $this->title = self::$foglang['PXEMenuCustomization'];
        printf('<p>%s</p><div id="tab-container-1">',_('This item allows you to edit all of the PXE Menu items as you see fit.  Mind you, iPXE syntax is very finicky when it comes to edits.  If you need help understanding what items are needed, please see the forums.  You can also look at ipxe.org for syntactic usage and methods.  Some of the items here are bound to limitations.  Documentation will follow when enough time is provided.'));
        $this->templates = array(
            '${field}',
            '${input}',
        );
        foreach ((array)self::getClass('PXEMenuOptionsManager')->find('','','id') AS $i => &$Menu) {
            if (!$Menu->isValid()) continue;
            $divTab = preg_replace('#[^\w\-]#','_',$Menu->get('name'));
            printf('<a id="%s" style="text-decoration:none;" href="#%s"><h3>%s</h3></a><div id="%s"><form method="post" action="%s">',$divTab,$divTab,$Menu->get('name'),$divTab,$this->formAction);
            $menuid = in_array($Menu->get('id'),range(1,13));
            $menuDefault = $Menu->get('default') ? ' checked' : '';
            $fields = array(
                _('Menu Item:') => sprintf('<input type="text" name="menu_item" value="%s" id="menu_item"/>',$Menu->get('name')),
                _('Description:') => sprintf('<textarea cols="40" rows="2" name="menu_description">%s</textarea>',$Menu->get('description')),
                _('Parameters:') => sprintf('<textarea cols="40" rows="8" name="menu_params">%s</textarea>',$Menu->get('params')),
                _('Boot Options:') => sprintf('<input type="text" name="menu_options" id="menu_options" value="%s"/>',$Menu->get('args')),
                _('Default Item:') => sprintf('<input type="checkbox" name="menu_default" value="1"%s/>',$menuDefault),
                _('Menu Show with:') => self::getClass('PXEMenuOptionsManager')->regSelect($Menu->get('regMenu')),
                sprintf('<input type="hidden" name="menu_id" value="%s"/>',$Menu->get('id')) => sprintf('<input type="submit" name="saveform" value="%s"/>',self::$foglang['Submit']),
                !$menuid ? sprintf('<input type="hidden" name="rmid" value="%s"/>',$Menu->get('id')) : '' => !$menuid ? sprintf('<input type="submit" name="delform" value="%s %s"/>',self::$foglang['Delete'],$Menu->get('name')) : '',
            );
            foreach ((array)$fields AS $field => &$input) {
                $this->data[] = array(
                    'field'=>$field,
                    'input'=>$input,
                );
                unset($input);
            }
            unset($fields);
            self::$HookManager->processEvent(sprintf('BOOT_ITEMS_%s',$divTab),array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes,'headerData'=>&$this->headerData));
            $this->render();
            echo '</form></div>';
            unset($this->data,$Menu);
        }
        echo '</div>';
    }
    public function customize_edit_post() {
        if (isset($_REQUEST['saveform']) && $_REQUEST['menu_id']) {
            self::getClass('PXEMenuOptionsManager')->update(array('id'=>$_REQUEST['menu_id']),'',array('name'=>$_REQUEST['menu_item'],'description'=>$_REQUEST['menu_description'],'params'=>$_REQUEST['menu_params'],'regMenu'=>$_REQUEST['menu_regmenu'],'args'=>$_REQUEST['menu_options'],'default'=>$_REQUEST['menu_default']));
            if ($_REQUEST['menu_default']) {
                $MenuIDs = self::getSubObjectIDs('PXEMenuOptions');
                natsort($MenuIDs);
                $MenuIDs = array_unique(array_diff($MenuIDs,(array)$_REQUEST['menu_id']));
                natsort($MenuIDs);
                self::getClass('PXEMenuOptionsManager')->update(array('id'=>$MenuIDs),'',array('default'=>'0'));
            }
            unset($MenuIDs);
            $DefMenuIDs = self::getSubObjectIDs('PXEMenuOptions',array('default'=>1));
            if (!count($DefMenuIDs)) self::getClass('PXEMenuOptions',1)->set('default',1)->save();
            unset($DefMenuIDs);
            $this->setMessage(sprintf('%s %s!',$_REQUEST['menu_item'],_('successfully updated')));
        }
        if (isset($_REQUEST['delform']) && $_REQUEST['rmid']) {
            $menuname = self::getClass('PXEMenuOptions',$_REQUEST['rmid'])->get('name');
            if (self::getClass('PXEMenuOptions',$_REQUEST['rmid'])->destroy()) $this->setMessage(sprintf('%s %s!',$menuname,_('successfully removed')));
        }
        $countDefault = self::getClass('PXEMenuOptionsManager')->count(array('default'=>1));
        if ($countDefault == 0 || $countDefault > 1) self::getClass('PXEMenuOptions',1)->set('default',1)->save();
        $this->redirect($this->formAction);
    }
    public function new_menu() {
        $this->title = _('Create New iPXE Menu Entry');
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $menudefault = $_REQUEST['menu_default'] ? ' checked' : '';
        $fields = array(
            _('Menu Item:') => sprintf('<input type="text" name="menu_item" value="%s" id="menu_item"/>',$_REQUEST['menu_item']),
            _('Description:') => sprintf('<textarea cols="40" rows="2" name="menu_description">%s</textarea>',$_REQUEST['menu_description']),
            _('Parameters:') => sprintf('<textarea cols="40" rows="8" name="menu_params">%s</textarea>',$_REQUEST['menu_params']),
            _('Boot Options:') => sprintf('<input type="text" name="menu_options" id="menu_options" value="%s"/>',$_REQUEST['menu_options']),
            _('Default Item:') => sprintf('<input type="checkbox" name="menu_default" value="1"%s/>',$menudefault),
            _('Menu Show with:') => self::getClass('PXEMenuOptionsManager')->regSelect($_REQUEST['menu_regmenu']),
            '&nbsp;' => sprintf('<input type="submit" value="%s %s"/>',self::$foglang['Add'],_('New Menu')),
        );
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field'=>$field,
                'input'=>$input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager->processEvent('BOOT_ITEMS_ADD',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes,'headerData'=>&$this->headerData));
        printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        echo "</form>";
    }
    public function new_menu_post() {
        try {
            if (!$_REQUEST['menu_item']) throw new Exception(_('Menu Item or title cannot be blank'));
            if (!$_REQUEST['menu_description']) throw new Exception(_('A description needs to be set'));
            if ($_REQUEST['menu_default']) self::getClass('PXEMenuOptionsManager')->update('','',array('default'=>0));
            $Menu = self::getClass('PXEMenuOptions')
                ->set('name',$_REQUEST['menu_item'])
                ->set('description',$_REQUEST['menu_description'])
                ->set('params',$_REQUEST['menu_params'])
                ->set('regMenu',$_REQUEST['menu_regmenu'])
                ->set('args',$_REQUEST['menu_options'])
                ->set('default',$_REQUEST['menu_default']);
            if (!$Menu->save()) throw new Exception(_('Menu create failed'));
            $countDefault = self::getClass('PXEMenuOptionsManager')->count(array('default'=>1));
            if ($countDefault == 0 || $countDefault > 1) self::getClass('PXEMenuOptions',1)->set('default',1)->save();
            self::$HookManager->processEvent('MENU_ADD_SUCCESS',array('Menu'=>&$Menu));
            $this->setMessage(_('Menu Added'));
            $this->redirect(sprintf('?node=%s&sub=edit&%s=%s',$this->node,$this->id,$Menu->get('id')));
        } catch (Exception $e) {
            self::$HookManager->processEvent('MENU_ADD_FAIL',array('Menu'=>&$Menu));
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
    public function client_updater() {
        $this->title = _("FOG Client Service Updater");
        $this->headerData = array(
            _('Module Name'),
            _('Module MD5'),
            _('Module Type'),
            _('Delete'),
        );
        $this->templates = array(
            '<input type="hidden" name="name" value="FOG_SERVICE_CLIENTUPDATER_ENABLED" />${name}',
            '${module}',
            '${type}',
            sprintf('<input type="checkbox" onclick="this.form.submit()" name="delcu" class="delid" id="delcuid${client_id}" value="${client_id}" /><label for="delcuid${client_id}" class="icon fa fa-minus-circle icon-hand" title="%s">&nbsp;</label>',_('Delete')),
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array('class'=>'filter-false'),
        );
        printf('<div class="hostgroup">%s</div>',_('This section allows you to update the modules and config files that run on the client computers.  The clients will checkin with the server from time to time to see if a new module is published.  If a new module is published the client will download the module and use it on the next time the service is started.'));
        foreach ((array)self::getClass('ClientUpdaterManager')->find() AS $i => &$ClientUpdate) {
            $this->data[] = array(
                'name'=>$ClientUpdate->get('name'),
                'module'=>$ClientUpdate->get('md5'),
                'type'=>$ClientUpdate->get('type'),
                'client_id'=>$ClientUpdate->get('id'),
                'id'=>$ClientUpdate->get('id'),
            );
            unset($ClientUpdate);
        }
        self::$HookManager->processEvent('CLIENT_UPDATE',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s&tab=clientupdater">',$this->formAction);
        $this->render();
        echo '</form>';
        unset($this->headerData,$this->attributes,$this->templates,$this->data);
        printf('<p class="header">%s</p>',_('Upload a new client module/configuration file'));
        $this->attributes = array(
            array(),
            array('class'=>'filter-false'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $fields = array(
            sprintf('<input type="file" name="module[]" value="" multiple/> <span class="lightColor">%s%s</span>',_('Max Size:'),ini_get('post_max_size')) => sprintf('<input type="submit" value="%s"/>',_('Upload File')),
        );
        foreach ((array)$fields AS $field => &$input) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
            unset($input);
        }
        unset($fields);
        self::$HookManager->processEvent('CLIENT_UPDATE',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<form method="post" action="%s&tab=clientupdater" enctype="multipart/form-data"><input type="hidden" name="name" value="FOG_SERVICE_CLIENTUPDATER_ENABLED"/>',$this->formAction);
        $this->render();
        echo '</form>';
    }
    public function client_updater_post() {
        if ($_REQUEST['delcu']) {
            self::getClass('ClientUpdaterManager')->destroy(array('id'=>$_REQUEST['delcu']));
            $this->setMessage(_('Client module update deleted!'));
        }
        try {
            if ($_FILES['module']['error'] > 0) throw new UploadException($_FILES['module']['error']);
            foreach ((array)$_FILES['module']['tmp_name'] AS $index => &$tmp_name) {
                if (!file_exists($tmp_name)) continue;
                if (!($md5 = md5(file_get_contents($tmp_name)))) continue;
                $filename = basename($_FILES['module']['name'][$index]);
                self::getClass('ClientUpdater',@max(self::getClass('ClientUpdater',array('name'=>$filename),'id')))
                    ->set('name',$filename)
                    ->set('md5',$md5)
                    ->set('type',$this->endsWith($filename,'.ini') ? 'txt' : 'bin')
                    ->set('file',file_get_contents($tmp_name))
                    ->save();
                unset($tmp_name);
            }
            $this->setMessage(_('Modules added/updated'));
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
        }
        $this->redirect(sprintf('%s#%s',$this->formAction,$_REQUEST['tab']));
    }
    public function mac_list() {
        $this->title = _('MAC Address Manufacturer Listing');
        printf('<div class="hostgroup">%s</div><div><p>%s: %s</p><p><div id="delete"></div><div id="update"></div><input class="macButtons" type="button" title="%s" value="%s" onclick="clearMacs()"/><input class="macButtons" style="margin-left: 20px" type="button" title="%s" value="%s" onclick="updateMacs()"/></p><p>%s<a href="http://standards.ieee.org/regauth/oui/oui.txt">http://standards.ieee.org/regauth/oui/oui.txt</a></p></div>',_('This section allows you to import known mac address makers into the FOG Database for easier identification.'),_('Current Records'),self::$FOGCore->getMACLookupCount(),_('Delete MACs'),_('Delete Current Records'),_('Update MACs'),_('Update Current Listing'),_('MAC Address listing source: '));
    }
    public function mac_list_post() {
        if ($_REQUEST['update']) {
            self::$FOGCore->clearMACLookupTable();
            $url = 'http://linuxnet.ca/ieee/oui.txt';
            if (($fh = fopen($url,'rb')) === false) throw new Exception(_('Could not read temp file'));
            $items = array();
            $start = 18;
            $imported = 0;
            while (($line = fgets($fh,4096)) !== false) {
                $line = trim($line);
                if (!preg_match("#^([0-9a-fA-F]{2}[:-]){2}([0-9a-fA-F]{2}).*$#",$line)) continue;
                $mac = trim(substr($line,0,8));
                $mak = trim(substr($line,$start,strlen($line)-$start));
                if (strlen($mac) != 8 || strlen($mak) < 1) continue;
                $items[] = array($mac,$mak);
            }
            fclose($fh);
            if (count($items)) {
                list($first_id,$affected_rows) = self::getClass('OUIManager')->insert_batch(array('prefix','name'),$items);
                $imported += $affected_rows;
                unset($items);
            }
            unset($first_id);
            $this->setMessage(sprintf('%s %s',$imported,_(' mac addresses updated!')));
        }
        if ($_REQUEST['clear']) self::$FOGCore->clearMACLookupTable();
        $this->resetRequest();
        $this->redirect('?node=about&sub=mac-list');
    }
    public function settings() {
        $ServiceNames = array(
            'FOG_REGISTRATION_ENABLED',
            'FOG_PXE_MENU_HIDDEN',
            'FOG_QUICKREG_AUTOPOP',
            'FOG_SERVICE_AUTOLOGOFF_ENABLED',
            'FOG_SERVICE_CLIENTUPDATER_ENABLED',
            'FOG_SERVICE_DIRECTORYCLEANER_ENABLED',
            'FOG_SERVICE_DISPLAYMANAGER_ENABLED',
            'FOG_SERVICE_GREENFOG_ENABLED',
            'FOG_SERVICE_HOSTREGISTER_ENABLED',
            'FOG_SERVICE_HOSTNAMECHANGER_ENABLED',
            'FOG_SERVICE_PRINTERMANAGER_ENABLED',
            'FOG_SERVICE_SNAPIN_ENABLED',
            'FOG_SERVICE_TASKREBOOT_ENABLED',
            'FOG_SERVICE_USERCLEANUP_ENABLED',
            'FOG_SERVICE_USERTRACKER_ENABLED',
            'FOG_ADVANCED_STATISTICS',
            'FOG_CHANGE_HOSTNAME_EARLY',
            'FOG_DISABLE_CHKDSK',
            'FOG_HOST_LOOKUP',
            'FOG_UPLOADIGNOREPAGEHIBER',
            'FOG_USE_ANIMATION_EFFECTS',
            'FOG_USE_LEGACY_TASKLIST',
            'FOG_USE_SLOPPY_NAME_LOOKUPS',
            'FOG_PLUGINSYS_ENABLED',
            'FOG_FORMAT_FLAG_IN_GUI',
            'FOG_NO_MENU',
            'FOG_MINING_ENABLE',
            'FOG_MINING_FULL_RUN_ON_WEEKEND',
            'FOG_ALWAYS_LOGGED_IN',
            'FOG_ADVANCED_MENU_LOGIN',
            'FOG_TASK_FORCE_REBOOT',
            'FOG_EMAIL_ACTION',
            'FOG_FTP_IMAGE_SIZE',
            'FOG_KERNEL_DEBUG',
            'FOG_ENFORCE_HOST_CHANGES',
        );
        $this->title = _('FOG System Settings');
        printf('<p class="hostgroup">%s</p><form method="post" action="%s"><div id="tab-container-1">',_('This section allows you to customize or alter the way in which FOG operates. Please be very careful changing any of the following settings, as they can cause issues that are difficult to troubleshoot.'),$this->formAction);
        unset($this->headerData);
        $this->attributes = array(
            array('width'=>270,'height'=>35),
            array(),
            array('class'=>'r'),
        );
        $this->templates = array(
            '${service_name}',
            '${input_type}',
            '${span}',
        );
        echo '<a href="#" class="trigger_expand"><h3>Expand All</h3></a>';
        foreach ((array)self::getClass('ServiceManager')->getSettingCats() AS $i => &$ServiceCAT) {
            $divTab = preg_replace('#[^\w\-]#','_',$ServiceCAT);
            printf('<a id="%s" class="expand_trigger" style="text-decoration:none;" href="#%s"><h3>%s</h3></a><div id="%s">',$divTab,$divTab,$ServiceCAT,$divTab);
            foreach ((array)self::getClass('ServiceManager')->find(array('category'=>$ServiceCAT),'AND','id') AS $i => &$Service) {
                if (!$Service->isValid()) continue;
                switch ($Service->get('name')) {
                case 'FOG_PIGZ_COMP':
                    $type = '<div id="pigz" style="width: 200px; top: 15px;"></div><input type="text" readonly="true" name="${service_id}" id="showVal" maxsize="1" style="width: 10px; top: -5px; left:225px; position: relative;" value="${service_value}"/>';
                    break;
                case 'FOG_KERNEL_LOGLEVEL':
                    $type = '<div id="loglvl" style="width: 200px; top: 15px;"></div><input type="text" readonly="true" name="${service_id}" id="showlogVal" maxsize="1" style="width: 10px; top: -5px; left:225px; position: relative;" value="${service_value}"/>';
                    break;
                case 'FOG_INACTIVITY_TIMEOUT':
                    $type = '<div id="inact" style="width: 200px; top: 15px;"></div><input type="text" readonly="true" name="${service_id}" id="showValInAct" maxsize="2" style="width: 15px; top: -5px; left:225px; position: relative;" value="${service_value}"/>';
                    break;
                case 'FOG_REGENERATE_TIMEOUT':
                    $type = '<div id="regen" style="width: 200px; top: 15px;"></div><input type="text" readonly="true" name="${service_id}" id="showValRegen" maxsize="5" style="width: 25px; top: -5px; left:225px; position: relative;" value="${service_value}"/>';
                    break;
                case 'FOG_VIEW_DEFAULT_SCREEN':
                    $screens = array('SEARCH','LIST');
                    ob_start();
                    foreach ((array)$screens AS $i => &$viewop) {
                        printf('<option value="%s"%s>%s</option>',strtolower($viewop),($Service->get('value') == strtolower($viewop) ? ' selected' : ''),$viewop);
                        unset($viewop);
                    }
                    unset($screens);
                    $type = sprintf('<select name="${service_id}" style="width: 220px" autocomplete="off">%s</select>',ob_get_clean());
                    break;
                case 'FOG_MULTICAST_DUPLEX':
                    $duplexTypes = array(
                        'HALF_DUPLEX' => '--half-duplex',
                        'FULL_DUPLEX' => '--full-duplex',
                    );
                    ob_start();
                    foreach ((array)$duplexTypes AS $types => &$val) {
                        printf('<option value="%s"%s>%s</option>',$val,($Service->get('value') == $val ? ' selected' : ''),$types);
                        unset($val);
                    }
                    $type = sprintf('<select name="${service_id}" style="width: 220px" autocomplete="off">%s</select>',ob_get_clean());
                    break;
                case 'FOG_BOOT_EXIT_TYPE':
                case 'FOG_EFI_BOOT_EXIT_TYPE':
                    $type = Service::buildExitSelector($Service->get('id'),$Service->get('value'));
                    break;
                case 'FOG_DEFAULT_LOCALE':
                    ob_start();
                    foreach ((array)self::$foglang['Language'] AS $lang => &$humanreadable) {
                        printf('<option value="%s"%s>%s</option>',$lang,(self::getSetting('FOG_DEFAULT_LOCALE') == $lang || self::getSetting('FOG_DEFAULT_LOCALE') == self::$foglang['Language'][$lang] ? ' selected' : ''),$humanreadable);
                        unset($humanreadable);
                    }
                    $type = sprintf('<select name="${service_id}" autocomplete="off" style="width: 220px">%s</select>',ob_get_clean());
                    break;
                case 'FOG_QUICKREG_IMG_ID':
                    $type = self::getClass('ImageManager')->buildSelectBox(self::getSetting('FOG_QUICKREG_IMG_ID'),sprintf('%s" id="${service_name}"',$Service->get('id')));
                    break;
                case 'FOG_QUICKREG_GROUP_ASSOC':
                    $type = self::getClass('GroupManager')->buildSelectBox(self::getSetting('FOG_QUICKREG_GROUP_ASSOC'),$Service->get('id'));
                    break;
                case 'FOG_KEY_SEQUENCE':
                    $type = self::getClass('KeySequenceManager')->buildSelectBox(self::getSetting('FOG_KEY_SEQUENCE'),$Service->get('id'));
                    break;
                case 'FOG_QUICKREG_OS_ID':
                    $ImageName = _('No image specified');
                    if (self::getSetting('FOG_QUICKREG_IMG_ID') > 0) $ImageName = self::getClass('Image',self::getSetting('FOG_QUICKREG_IMG_ID'))->get('name');
                    $type = sprintf('<p id="${service_name}">%s</p>',$ImageName);
                    break;
                case 'FOG_TZ_INFO':
                    $dt = self::nice_date('now',$utc);
                    $tzIDs = DateTimeZone::listIdentifiers();
                    ob_start();
                    echo '<select name="${service_id}">';
                    foreach ((array)$tzIDs AS $i => &$tz) {
                        $current_tz = self::getClass('DateTimeZone',$tz);
                        $offset = $current_tz->getOffset($dt);
                        $transition = $current_tz->getTransitions($dt->getTimestamp(),$dt->getTimestamp());
                        $abbr = $transition[0]['abbr'];
                        $offset = sprintf('%+03d:%02u', floor($offset / 3600), floor(abs($offset) % 3600 / 60));
                        printf('<option value="%s"%s>%s [%s %s]</option>',$tz,($Service->get('value') == $tz ? ' selected' : ''),$tz,$abbr,$offset);
                        unset($current_tz,$offset,$transition,$abbr,$offset,$tz);
                    }
                    echo '</select>';
                    $type = ob_get_clean();
                    break;
                case (preg_match('#pass#i',$Service->get('name')) && !preg_match('#(valid|min)#i',$Service->get('name'))):
                    $Service->get('name') == 'FOG_STORAGENODE_MYSQLPASS' ? $type = '<input type="text" name="${service_id}" value="${service_value}" autocomplete="off"/>' : $type = '<input type="password" name="${service_id}" value="${service_value}" autocomplete="off"/>';
                    break;
                case (in_array($Service->get('name'),$ServiceNames)):
                    $type = sprintf('<input type="checkbox" name="${service_id}" value="1"%s/>',($Service->get('value') ? ' checked' : ''));
                    break;
                case 'FOG_AD_DEFAULT_OU':
                    $type = '<textarea rows="5" name="${service_id}">${service_value}</textarea>';
                    break;
                default:
                    $type = '<input id="${service_name}" type="text" name="${service_id}" value="${service_value}" autocomplete="off"/>';
                    break;
                }
                $this->data[] = array(
                    'input_type'=>(count(explode(chr(10),$Service->get('value'))) <= 1 ? $type : '<textarea rows="5" name="${service_id}">${service_value}</textarea>'),
                    'service_name'=> $Service->get('name'),
                    'span'=>'<i class="icon fa fa-question hand" title="${service_desc}"></i>',
                    'service_id'=>$Service->get('id'),
                    'id'=>$Service->get('id'),
                    'service_value'=>$Service->get('value'),
                    'service_desc'=>$Service->get('description'),
                );
                unset($Service);
            }
            unset($ServMan);
            $this->data[] = array(
                'span'=>'&nbsp;',
                'service_name'=>'',
                'input_type'=>sprintf('<input name="update" type="submit" value="%s"/>',_('Save Changes')),
            );
            self::$HookManager->processEvent(sprintf('CLIENT_UPDATE_%s',$divTab),array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
            $this->render();
            echo '</div>';
            unset($this->data,$options,$ServiceCAT);
        }
        unset($ServiceCats);
        echo '</div></form>';
    }
    public function getOSID() {
        $imageid = (int) $_REQUEST['image_id'];
        $osname = self::getClass('Image',$imageid)->getOS()->get('name');
        echo json_encode($osname ? $osname : _('No Image specified'));
        exit;
    }
    public function settings_post() {
        $checkbox = array(0,1);
        $regenrange = range(0,24,.25);
        array_shift($regenrange);
        $needstobenumeric = array(
            // Donations
            'FOG_MINING_ENABLE' => $checkbox,
            'FOG_MINING_MAX_CORES' => true,
            'FOG_MINING_FULL_RESTART_HOUR' => range(0,23),
            'FOG_MINING_FULL_RUN_ON_WEEKEND' => $checkbox,
            // FOG Boot Settings
            'FOG_PXE_MENU_TIMEOUT' => true,
            'FOG_PXE_MENU_HIDDEN' => $checkbox,
            'FOG_PIGZ_COMP' => range(0,9),
            'FOG_KEY_SEQUENCE' => range(1,31),
            'FOG_NO_MENU' => $checkbox,
            'FOG_ADVANCED_MENU_LOGIN' => $checkbox,
            'FOG_KERNEL_DEBUG' => $checkbox,
            'FOG_PXE_HIDDENMENU_TIMEOUT' => true,
            'FOG_REGISTRATION_ENABLED' => $checkbox,
            'FOG_KERNEL_LOGLEVEL' => range(0,7),
            'FOG_WIPE_TIMEOUT' => true,
            // FOG Email Settings
            'FOG_EMAIL_ACTION' => $checkbox,
            // FOG Linux Service Logs
            'SERVICE_LOG_SIZE' => true,
            // FOG Linux Service Sleep Times
            'PINGHOSTSLEEPTIME' => true,
            'SERVICESLEEPTIME' => true,
            'SNAPINREPSLEEPTIME' => true,
            'SCHEDULERSLEEPTIME' => true,
            'IMAGEREPSLEEPTIME' => true,
            'MULTICASESLEEPTIME' => true,
            // FOG Quick Registration
            'FOG_QUICKREG_AUTOPOP' => $checkbox,
            'FOG_QUICKREG_IMG_ID' => array_merge((array)0,self::getSubObjectIDs('Image')),
            'FOG_QUICKREG_SYS_NUMBER' => true,
            'FOG_QUICKREG_GROUP_ASSOC' => array_merge((array)0,self::getSubObjectIDs('Group')),
            // FOG Service
            'FOG_SERVICE_CHECKIN_TIME' => true,
            'FOG_CLIENT_MAXSIZE' => true,
            'FOG_GRACE_TIMEOUT' => true,
            // FOG Service - Auto Log Off
            'FOG_SERVICE_AUTOLOGOFF_ENABLED' => $checkbox,
            'FOG_SERVICE_AUTOLOGOFF_MIN' => true,
            // FOG Service - Client Updater
            'FOG_SERVICE_CLIENTUPDATER_ENABLED' => $checkbox,
            // FOG Service - Directory Cleaner
            'FOG_SERVICE_DIRECTORYCLEANER_ENABLED' => $checkbox,
            // FOG Service - Display manager
            'FOG_SERVICE_DISPLAYMANAGER_ENABLED' => $checkbox,
            'FOG_SERVICE_DISPLAYMANAGER_X' => true,
            'FOG_SERVICE_DISPLAYMANAGER_Y' => true,
            'FOG_SERVICE_DISPLAYMANAGER_R' => true,
            // FOG Service - Green Fog
            'FOG_SERVICE_GREENFOG_ENABLED' => $checkbox,
            // FOG Service - Host Register
            'FOG_SERVICE_HOSTREGISTER_ENABLED' => $checkbox,
            'FOG_QUICKREG_MAX_PENDING_MACS' => true,
            // FOG Service - Hostname Changer
            'FOG_SERVICE_HOSTNAMECHANGER_ENABLED' => $checkbox,
            // FOG Service - Printer Manager
            'FOG_SERVICE_PRINTERMANAGER_ENABLED' => $checkbox,
            // FOG Service - Snapins
            'FOG_SERVICE_SNAPIN_ENABLED' => $checkbox,
            // FOG Service - Task Reboot
            'FOG_SERVICE_TASKREBOOT_ENABLED' => $checkbox,
            'FOG_TASK_FORCE_ENABLED' => $checkbox,
            // FOG Service - User Cleanup
            'FOG_SERVICE_USERCLEANUP_ENABLED' => $checkbox,
            // FOG Service - User Tracker
            'FOG_SERVICE_USERTRACKER_ENABLED' => $checkbox,
            // FOG View Settings
            'FOG_DATA_RETURNED' => true,
            // General Settings
            'FOG_USE_SLOPPY_NAME_LOOKUPS' => $checkbox,
            'FOG_UPLOADRESIZEPCT' => true,
            'FOG_QUEUESIZE' => true,
            'FOG_CHECKIN_TIMEOUT' => true,
            'FOG_UPLOADIGNOREPAGEHIBER' => $checkbox,
            'FOG_USE_ANIMATION_EFFECTS' => $checkbox,
            'FOG_USE_LEGACY_TASKLIST' => $checkbox,
            'FOG_HOST_LOOKUP' => $checkbox,
            'FOG_ADVANCED_STATISTICS' => $checkbox,
            'FOG_DISABLE_CHKDSK' => $checkbox,
            'FOG_CHANGE_HOSTNAME_EARLY' => $checkbox,
            'FOG_FORMAT_FLAG_IN_GUI' => $checkbox,
            'FOG_MEMORY_LIMIT' => true,
            'FOG_SNAPIN_LIMIT' => true,
            'FOG_FTP_IMAGE_SIZE' => $checkbox,
            'FOG_FTP_PORT' => range(1,65535),
            'FOG_FTP_TIMEOUT' => true,
            'FOG_BANDWIDTH_TIME' => true,
            // Login Settings
            'FOG_ALWAYS_LOGGED_IN' => $checkbox,
            'FOG_INACTIVITY_TIMEOUT' => range(1,24),
            'FOG_REGENERATE_TIMEOUT' => $regenrange,
            // Multicast Settings
            'FOG_UDPCAST_STARTINGPORT' => range(1,65535),
            'FOG_MULTICASE_MAX_SESSIONS' => true,
            'FOG_UDPCAST_MAXWAIT' => true,
            'FOG_MULTICAST_PORT_OVERRIDE' => range(0,65535),
            // Plugin System
            'FOG_PLUGINSYS_ENABLED' => $checkbox,
            // Proxy Settings
            'FOG_PROXY_PORT' => range(0,65535),
            // User Management
            'FOG_USER_MINPASSLENGTH' => true,
        );
        $needstobeip = array(
            // Multicast Settings
            'FOG_MULTICAST_ADDRESS' => true,
            // Proxy Settings
            'FOG_PROXY_IP' => true,
        );
        unset($findWhere,$setWhere);
        foreach ((array)self::getClass('ServiceManager')->find() AS $i => &$Service) {
            $key = $Service->get('id');
            $_REQUEST[$key] = trim($_REQUEST[$key]);
            if ($_REQUEST[$key] == trim($Service->get('value'))) continue;
            if (isset($needstobenumeric[$Service->get('name')])) {
                if ($needstobenumeric[$Service->get('name')] === true && !is_numeric($_REQUEST[$key])) $_REQUEST[$key] = 0;
                if ($needstobenumeric[$Service->get('name')] !== true && !in_array($_REQUEST[$key],$needstobenumeric[$Service->get('name')])) $_REQUEST[$key] = 0;
            }
            if (isset($needstobeip[$Service->get('name')]) && !filter_var($_REQUEST[$key],FILTER_VALIDATE_IP)) $_REQUEST[$key] = 0;
            switch ($Service->get('name')) {
            case 'FOG_MEMORY_LIMIT':
                if ($_REQUEST[$key] < 128) $_REQUEST[$key] = 128;
                break;
            case 'FOG_AD_DEFAULT_PASSWORD':
                $_REQUEST[$key] = $this->encryptpw($_REQUEST[$key]);
                break;
            default:
                break;
            }
            $Service->set('value',$_REQUEST[$key])->save();
            unset($Service);
        }
        $this->setMessage('Settings Successfully stored!');
        $this->redirect($this->formAction);
    }
    public function logviewer() {
        foreach ((array)self::getClass('StorageGroupManager')->find() AS $i => &$StorageGroup) {
            if (!$StorageGroup->isValid()) continue;
            if (!count($StorageGroup->get('enablednodes'))) continue;
            $StorageNode = $StorageGroup->getMasterStorageNode();
            if (!$StorageNode->isValid()) continue;
            if (!$StorageNode->get('isEnabled')) continue;
            $fogfiles = (array)$StorageNode->get('logfiles');
            try {
                $apacheerrlog = preg_grep('#(error\.log$|.*error_log$)#i',$fogfiles);
                $apacheacclog = preg_grep('#(access\.log$|.*access_log$)#i',$fogfiles);
                $multicastlog = preg_grep('#(multicast.log$)#i',$fogfiles);
                $multicastlog = @array_shift($multicastlog);
                $schedulerlog = preg_grep('#(fogscheduler.log$)#i',$fogfiles);
                $schedulerlog = @array_shift($schedulerlog);
                $imgrepliclog = preg_grep('#(fogreplicator.log$)#i',$fogfiles);
                $imgrepliclog = @array_shift($imgrepliclog);
                $snapinreplog = preg_grep('#(fogsnapinrep.log$)#i',$fogfiles);
                $snapinreplog = @array_shift($snapinreplog);
                $pinghostlog = preg_grep('#(pinghosts.log$)#i',$fogfiles);
                $pinghostlog = @array_shift($pinghostlog);
                $svcmasterlog = preg_grep('#(servicemaster.log$)#i',$fogfiles);
                $svcmasterlog = @array_shift($svcmasterlog);
                $imgtransferlogs = preg_grep('#(fogreplicator.log.transfer)#i',$fogfiles);
                $snptransferlogs = preg_grep('#(fogsnapinrep.log.transfer)#i',$fogfiles);
                $files[$StorageNode->get('name')] = array(
                    $svcmasterlog ? _('Service Master') : null => $svcmasterlog ? $svcmasterlog : null,
                    $multicastlog ? _('Multicast') : null => $multicastlog ? $multicastlog : null,
                    $schedulerlog ? _('Scheduler') : null => $schedulerlog ? $schedulerlog : null,
                    $imgrepliclog ? _('Image Replicator') : null => $imgrepliclog ? $imgrepliclog : null,
                    $snapinreplog ? _('Snapin Replicator') : null => $snapinreplog ? $snapinreplog : null,
                    $pinghostlog ? _('Ping Hosts') : null => $pinghostlog ? $pinghostlog : null,
                );
                $logtype = 'error';
                $logparse = function(&$log) use (&$files,$StorageNode,&$logtype) {
                    $files[$StorageNode->get('name')][_(sprintf('%s %s log (%s)',preg_match('#nginx#i',$log) ? 'NGINX' : (preg_match('#apache|httpd#',$log) ? 'Apache' : (preg_match('#fpm#i',$log) ? 'PHP-FPM' : '')),$logtype,basename($log)))] = $log;
                };
                array_map($logparse,(array)$apacheerrlog);
                $logtype = 'access';
                array_map($logparse,(array)$apacheacclog);
                foreach ((array)$imgtransferlogs AS &$file) {
                    $files[$StorageNode->get('name')][sprintf('%s %s',$this->string_between($file,'transfer.','.log'),_('Image Transfer Log'))] = $file;
                    unset($file);
                }
                foreach ((array)$snptransferlogs AS &$file) {
                    $files[$StorageNode->get('name')][sprintf('%s %s',$this->string_between($file,'transfer.','.log'),_('Snapin Transfer Log'))] = $file;
                    unset($file);
                }
                $files[$StorageNode->get('name')] = array_filter((array)$files[$StorageNode->get('name')]);
            } catch (Exception $e) {
                $files[$StorageNode->get('name')] = array(
                    $e->getMessage() => null,
                );
            }
            $ip[$StorageNode->get('name')] = $StorageNode->get('ip');
            self::$HookManager->processEvent('LOG_VIEWER_HOOK',array('files'=>&$files,'StorageNode'=>&$StorageNode));
            unset($StorageGroup);
        }
        unset($StorageGroups);
        ob_start();
        foreach ((array)$files AS $nodename => &$filearray) {
            $first = true;
            foreach((array)$filearray AS $value => &$file) {
                if ($first) {
                    printf('<option disabled> ------- %s ------- </option>',$nodename);
                    $first = false;
                }
                printf('<option value="%s||%s"%s>%s</option>',$this->aesencrypt($ip[$nodename]),$file,($value == $_REQUEST['logtype'] ? ' selected' : ''),$value);
                unset($file);
            }
            unset($filearray);
        }
        unset($files);
        $this->title = _('FOG Log Viewer');
        printf('<p><form method="post" action="%s"><p>%s:<select name="logtype" id="logToView">%s</select>%s:',$this->formAction,_('File'),ob_get_clean(),_('Number of lines'));
        $vals = array(20,50,100,200,400,500,1000);
        ob_start();
        foreach ((array)$vals AS $i => &$value) {
            printf('<option value="%s"%s>%s</option>',$value,($value == $_REQUEST['n'] ? ' selected' : ''),$value);
            unset($value);
        }
        unset($vals);
        printf('<select name="n" id="linesToView">%s</select><br/><p class="c"><label for="reverse">%s : <input type="checkbox" name="reverse" id="reverse"/></label></p></label><br/><p class="c"><input type="button" id="logpause"/></p></p></form><br/><div id="logsGoHere"></div></p>',ob_get_clean(),_('Reverse the file: (newest on top)'));
    }
    public function config() {
        self::$HookManager->processEvent('IMPORT');
        $this->title='Configuration Import/Export';
        $report = self::getClass('ReportMaker');
        $_SESSION['foglastreport']=serialize($report);
        unset($this->data,$this->headerData);
        $this->attributes = array(
            array(),
            array('class'=>'r'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->data[] = array(
            'field' => _('Click the button to export the database.'),
            'input' => sprintf('<input type="submit" name="export" value="%s"/>',_('Export')),
        );
        echo '<form method="post" action="export.php?type=sql">';
        $this->render();
        unset($this->data);
        echo '</form>';
        $this->data[] = array(
            'field' => _('Import a previous backup file.'),
            'input' => '<span class="lightColor">Max Size: ${size}</span><input type="file" name="dbFile" />',
            'size' => ini_get('post_max_size'),
        );
        $this->data[] = array(
            'field' => null,
            'input' => sprintf('<input type="submit" value="%s"/>',_('Import')),
        );
        printf('<form method="post" action="%s" enctype="multipart/form-data">',$this->formAction);
        $this->render();
        echo "</form>";
        unset($this->attributes,$this->templates,$this->data);
    }
    public function config_post() {
        self::$HookManager->processEvent('IMPORT_POST');
        $Schema = self::getClass('Schema');
        try {
            if ($_FILES['dbFile']['error'] > 0) throw new UploadException($_FILES['dbFile']['error']);
            $original = $Schema->export_db('',false);
            $tmp_name = htmlentities($_FILES['dbFile']['tmp_name'],ENT_QUOTES,'utf-8');
            $filename = sprintf('%s%s%s',dirname($tmp_name),DIRECTORY_SEPARATOR,basename($tmp_name));
            $result = self::getClass('Schema')->import_db($filename);
            if ($result === true) printf('<h2>%s</h2>',_('Database Imported and added successfully'));
            else {
                printf('<h2>%s</h2>',_('Errors detected on import'));
                $origres = $result;
                $result = $Schema->import_db($original);
                unlink($original);
                unset($original);
                if ($result === true) printf('<h2>%s</h2>',_('Database changes reverted'));
                else printf('%s<br/><br/><code><pre>%s</pre></code>',_('Errors on revert detected'),$result);
                printf('<h2>%s</h2><code><pre>%s</pre></code>',_('There were errors during import'),$origres);
            }
        } catch (Exception $e) {
            $this->setMessage($e->getMessage());
            $this->redirect($this->formAction);
        }
    }
}
