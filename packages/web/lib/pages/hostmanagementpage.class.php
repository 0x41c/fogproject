<?php
class HostManagementPage extends FOGPage {
    public $node = 'host';
    public function __construct($name = '') {
        $this->name = 'Host Management';
        parent::__construct($this->name);
        if ($_SESSION['Pending-Hosts']) $this->menu['pending'] = self::$foglang['PendingHosts'];
        if ($_REQUEST['id']) {
            $this->subMenu = array(
                "$this->linkformat#host-general"=>self::$foglang['General'],
            );
            if (!$this->obj->get('pending')) $this->subMenu = array_merge($this->subMenu,array("$this->linkformat#host-tasks"=>self::$foglang['BasicTasks']));
            $this->subMenu = array_merge($this->subMenu,array(
                "$this->linkformat#host-active-directory"=>self::$foglang['AD'],
                "$this->linkformat#host-printers"=>self::$foglang['Printers'],
                "$this->linkformat#host-snapins"=>self::$foglang['Snapins'],
                "$this->linkformat#host-service"=>sprintf('%s %s',self::$foglang['Service'],self::$foglang['Settings']),
                "$this->linkformat#host-powermanagement"=>self::$foglang['PowerManagement'],
                "$this->linkformat#host-hardware-inventory"=>self::$foglang['Inventory'],
                "$this->linkformat#host-virus-history"=>self::$foglang['VirusHistory'],
                "$this->linkformat#host-login-history"=>self::$foglang['LoginHistory'],
                "$this->linkformat#host-image-history"=>self::$foglang['ImageHistory'],
                "$this->linkformat#host-snapin-history"=>self::$foglang['SnapinHistory'],
                $this->membership=>self::$foglang['Membership'],
                $this->delformat=>self::$foglang['Delete'],
            ));
            $this->notes = array(
                self::$foglang['Host']=>$this->obj->get('name'),
                self::$foglang['MAC']=>$this->obj->get('mac'),
                self::$foglang['Image']=>$this->obj->getImageName(),
                self::$foglang['LastDeployed']=>$this->obj->get('deployed'),
            );
            $Group = self::getClass('Group',@min($this->obj->get('groups')));
            if ($Group->isValid()) {
                $this->notes[self::$foglang['PrimaryGroup']] = $Group->get('name');
                unset($Group);
            }
        }
        $this->exitNorm = Service::buildExitSelector('bootTypeExit',($this->obj && $this->obj->isValid() ? $this->obj->get('biosexit') : $_REQUEST['bootTypeExit']),true);
        $this->exitEfi = Service::buildExitSelector('efiBootTypeExit',($this->obj && $this->obj->isValid() ? $this->obj->get('efiexit') : $_REQUEST['efiBootTypeExit']),true);
        self::$HookManager->processEvent('SUB_MENULINK_DATA',array('menu'=>&$this->menu,'submenu'=>&$this->subMenu,'id'=>&$this->id,'notes'=>&$this->notes,'biosexit'=>&$this->exitNorm,'efiexit'=>&$this->exitEfi,'object'=>&$this->obj,'linkformat'=>&$this->linkformat,'delformat'=>&$this->delformat,'membership'=>&$this->membership));
        $this->headerData = array(
            '',
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
        );
        $_SESSION['FOGPingActive'] ? array_push($this->headerData,'') : null;
        array_push($this->headerData,
            _('Host'),
            _('Imaged'),
            _('Task'),
            _('Assigned Image')
        );
        $this->templates = array(
            '<span class="icon fa fa-question hand" title="${host_desc}"></span>',
            '<input type="checkbox" name="host[]" value="${id}" class="toggle-action"/>',
        );
        $_SESSION['FOGPingActive'] ? array_push($this->templates,'${pingstatus}') : null;
        $up = self::getClass('TaskType',2);
        $down = self::getClass('TaskType',1);
        $mc = self::getClass('TaskType',8);
        array_push($this->templates,
            '<a href="?node=host&sub=edit&id=${id}" title="Edit: ${host_name}" id="host-${host_name}">${host_name}</a><br /><small>${host_mac}</small>',
            '<small>${deployed}</small>',
            sprintf('<a href="?node=host&sub=deploy&sub=deploy&type=1&id=${id}"><i class="icon fa fa-%s" title="%s"></i></a> <a href="?node=host&sub=deploy&sub=deploy&type=2&id=${id}"><i class="icon fa fa-%s" title="%s"></i></a> <a href="?node=host&sub=deploy&type=8&id=${id}"><i class="icon fa fa-%s" title="%s"></i></a> <a href="?node=host&sub=edit&id=${id}#host-tasks"><i class="icon fa fa-arrows-alt" title="Goto Task List"></i></a>',$down->get('icon'),$down->get('name'),$up->get('icon'),$up->get('name'),$mc->get('icon'),$mc->get('name')),
            '<a href="?node=image&sub=edit&id=${image_id}">${image_name}</a>'
        );
        unset($up,$down,$mc);
        $this->attributes = array(
            array('width'=>16,'id'=>'host-${host_name}','class'=>'l filter-false'),
            array('class'=>'l filter-false','width'=>16),
        );
        $_SESSION['FOGPingActive'] ? array_push($this->attributes,array('width'=>16,'class'=>'l filter-false')) : null;
        array_push($this->attributes,
            array('width'=>50),
            array('width'=>145),
            array('width'=>80,'class'=>'r filter-false'),
            array('width'=>40,'class'=>'r filter-false'),
            array('width'=>20,'class'=>'r')
        );
        self::$returnData = function(&$Host) {
            if (!$Host->isValid()) return;
            $this->data[] = array(
                'id'=>$Host->get('id'),
                'deployed'=>$this->formatTime($Host->get('deployed'),'Y-m-d H:i:s'),
                'host_name'=>$Host->get('name'),
                'host_mac'=>$Host->get('mac')->__toString(),
                'host_desc'=>$Host->get('description'),
                'image_id'=>$Host->get('imageID'),
                'image_name'=>$Host->getImageName(),
                'pingstatus'=>$Host->getPingCodeStr(),
            );
            unset($Host);
        };
    }
    public function index() {
        $this->title = self::$foglang['AllHosts'];
        if ($_SESSION['DataReturn'] > 0 && $_SESSION['HostCount'] > $_SESSION['DataReturn'] && $_REQUEST['sub'] != 'list') $this->redirect(sprintf('?node=%s&sub=search',$this->node));
        $this->data = array();
        array_map(self::$returnData,self::getClass($this->childClass)->getManager()->find(array('pending'=>array((string)'0',(string)'',null))));
        self::$HookManager->processEvent('HOST_DATA',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        self::$HookManager->processEvent('HOST_HEADER_DATA',array('headerData'=>&$this->headerData,'title'=>&$this->title));
        $this->render();
    }
    public function search_post() {
        $this->data = array();
        array_map(self::$returnData,self::getClass($this->childClass)->getManager()->search('',true));
        self::$HookManager->processEvent('HOST_DATA',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        self::$HookManager->processEvent('HOST_HEADER_DATA',array('headerData'=>&$this->headerData));
        $this->render();
    }
    public function pending() {
        $this->title = _('Pending Host List');
        $this->data = array();
        array_map(self::$returnData,self::getClass($this->childClass)->getManager()->find(array('pending'=>(string)1)));
        self::$HookManager->processEvent('HOST_DATA',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        self::$HookManager->processEvent('HOST_HEADER_DATA',array('headerData'=>&$this->headerData));
        if (count($this->data) > 0) printf('<form method="post" action="%s">',$this->formAction);
        $this->render();
        if (count($this->data) > 0) printf('<p class="c"><input name="approvependhost" type="submit" value="%s"/>&nbsp;&nbsp;<input name="delpendhost" type="submit" value="%s"/></p></form>',_('Approve selected Hosts'),_('Delete selected Hosts'));
    }
    public function pending_post() {
        if (isset($_REQUEST['approvependhost'])) self::getClass('HostManager')->update(array('id'=>$_REQUEST['host']),'',array('pending'=>(string)0));
        if (isset($_REQUEST['delpendhost'])) self::getClass('HostManager')->destroy(array('id'=>$_REQUEST['host']));
        $appdel = (isset($_REQUEST['approvependhost']) ? 'approved' : 'deleted');
        $this->setMessage(_("All hosts $appdel successfully"));
        $this->redirect("?node=$this->node");
    }
    public function add() {
        $this->title = _('New Host');
        unset($this->data);
        $this->headerData = '';
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array(),
            array(),
        );
        $fields = array(
            _('Host Name') => sprintf('<input type="text" name="host" value="%s" maxlength="15" class="hostname-input"/>*',$_REQUEST['host']),
            _('Primary MAC') => sprintf('<input type="text" id="mac" name="mac" value="%s"/>*<span id="priMaker"></span><span class="mac-manufactor"></span><i class="icon add-mac fa fa-plus-circle hand" title="%s"></i>',$_REQUEST['mac'],_('Add MAC')),
            _('Host Description') => sprintf('<textarea name="description" rows="8" cols="40">%s</textarea>',$_REQUEST['description']),
            _('Host Product Key') => sprintf('<input id="productKey" type="text" name="key" value="%s"/>',$_REQUEST['key']),
            _('Host Image') => self::getClass('ImageManager')->buildSelectBox($_REQUEST['image'],'','id'),
            _('Host Kernel') => sprintf('<input type="text" name="kern" value="%s"/>',$_REQUEST['kern']),
            _('Host Kernel Arguments') => sprintf('<input type="text" name="args" value="%s"/>',$_REQUEST['args']),
            _('Host Init') => sprintf('<input type="text" name="init" value="%s"/>',$_REQUEST['init']),
            _('Host Primary Disk') => sprintf('<input type="text" name="dev" value="%s"/>',$_REQUEST['dev']),
            _('Host Bios Exit Type') => $this->exitNorm,
            _('Host EFI Exit Type') => $this->exitEfi,
        );
        printf('<h2>%s</h2><form method="post" action="%s">',_('Add new host definition'),$this->formAction);
        self::$HookManager->processEvent('HOST_FIELDS',array('fields'=>&$fields,'Host'=>self::getClass('Host')));
        array_walk($fields,function(&$input,&$field) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
            unset($field,$input);
        });
        self::$HookManager->processEvent('HOST_ADD_GEN',array('data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes,'fields'=>&$fields));
        $this->render();
        echo $this->adFieldsToDisplay();
        echo '</form>';
    }
    public function add_post() {
        self::$HookManager->processEvent('HOST_ADD_POST');
        try {
            $hostName = trim($_REQUEST['host']);
            if (empty($hostName)) throw new Exception(_('Please enter a hostname'));
            if (!self::getClass('Host')->isHostnameSafe($hostName)) throw new Exception(_('Please enter a valid hostname'));
            if (self::getClass('HostManager')->exists($hostName)) throw new Exception(_('Hostname Exists already'));
            if (empty($_REQUEST['mac'])) throw new Exception(_('MAC Address is required'));
            $MAC = self::getClass('MACAddress',$_REQUEST['mac']);
            if (!$MAC->isValid()) throw new Exception(_('MAC Format is invalid'));
            $Host = self::getClass('HostManager')->getHostByMacAddresses($MAC);
            if ($Host && $Host->isValid()) throw new Exception(sprintf(_('A host with this MAC already exists with Hostname: %s'),$Host->get('name')));
            $ModuleIDs = self::getSubObjectIDs('Module');
            $password = $_REQUEST['domainpassword'];
            if ($_REQUEST['domainpassword']) $password = $this->encryptpw($_REQUEST['domainpassword']);
            $useAD = isset($_REQUEST['domain']);
            $domain = trim($_REQUEST['domainname']);
            $ou = trim($_REQUEST['ou']);
            $user = trim($_REQUEST['domainuser']);
            $pass = $password;
            $passlegacy = trim($_REQUEST['domainpasswordlegacy']);
            $productKey = preg_replace('/([\w+]{5})/','$1-',str_replace('-','',strtoupper(trim($_REQUEST['key']))));
            $productKey = substr($productKey,0,29);
            $enforce = (string)intval(isset($_REQUEST['enforcesel']));
            $Host = self::getClass('Host')
                ->set('name',$hostName)
                ->set('description',$_REQUEST['description'])
                ->set('imageID',$_REQUEST['image'])
                ->set('kernel',$_REQUEST['kern'])
                ->set('kernelArgs',$_REQUEST['args'])
                ->set('kernelDevice',$_REQUEST['dev'])
                ->set('init',$_REQUEST['init'])
                ->set('biosexit',$_REQUEST['bootTypeExit'])
                ->set('efiexit',$_REQUEST['efiBootTypeExit'])
                ->set('productKey',$this->encryptpw($productKey))
                ->addModule($ModuleIDs)
                ->addPriMAC($MAC)
                ->setAD($useAD,$domain,$ou,$user,$pass,true,$passlegacy,$productKey,$enforce);
            if (!$Host->save()) throw new Exception(_('Host create failed'));
            self::$HookManager->processEvent('HOST_ADD_SUCCESS',array('Host'=>&$Host));
            $this->setMessage(_('Host added'));
            $url = sprintf('?node=%s&sub=edit&id=%s',$_REQUEST['node'],$Host->get('id'));
        } catch (Exception $e) {
            self::$HookManager->processEvent('HOST_ADD_FAIL',array('Host'=>&$Host));
            $this->setMessage($e->getMessage());
            $url = $this->formAction;
        }
        unset($Host,$passlegacy,$pass,$user,$ou,$domain,$useAD,$password,$ModuleIDs,$MAC,$hostName);
        $this->redirect($url);
    }
    public function edit() {
        $this->title = sprintf('%s: %s',_('Edit'),$this->obj->get('name'));
        if ($_REQUEST['approveHost']) {
            $this->obj->set('pending',null);
            if ($this->obj->save()) $this->setMessage(_('Host approved'));
            else $this->setMessage(_('Host approval failed.'));
            $this->redirect(sprintf('?node=%s&sub=edit&id=%s#host-general',$this->node,$_REQUEST['id']));
        }
        if ($this->obj->get('pending')) printf('<h2><a href="%s&approveHost=1">%s</a></h2>',$this->formAction,_('Approve this host?'));
        unset($this->headerData);
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        if ($_REQUEST['confirmMAC']) {
            try {
                $this->obj->addPendtoAdd($_REQUEST['confirmMAC']);
                if ($this->obj->save()) $this->setMessage('MAC: '.$_REQUEST['confirmMAC'].' Approved!');
            } catch (Exception $e) {
                $this->setMessage($e->getMessage());
            }
            $this->redirect(sprintf('?node=%s&sub=edit&id=%s#host-general',$this->node,$_REQUEST['id']));
        }
        else if ($_REQUEST['approveAll']) {
            self::getClass('MACAddressAssociationManager')->update(array('hostID'=>$this->obj->get('id')),'',array('pending'=>(string)0));
            $this->setMessage('All Pending MACs approved.');
            $this->redirect(sprintf('?node=%s&sub=edit&id=%s#host-general',$this->node,$_REQUEST['id']));
        }
        ob_start();
        foreach ((array)$this->obj->get('additionalMACs') AS $i => &$MAC) {
            if (!$MAC->isValid()) continue;
            printf('<div><input class="additionalMAC" type="text" name="additionalMACs[]" value="%s"/>&nbsp;&nbsp;<i class="icon fa fa-minus-circle remove-mac hand" title="%s"></i><span class="icon icon-hand" title="%s"><input type="checkbox" name="igclient[]" value="%s" %s/></span><span class="icon icon-hand" title="%s"><input type="checkbox" name="igimage[]" value="%s" %s/></span><br/><span class="mac-manufactor"></span></div>',$MAC,_('Remove MAC'),_('Ignore MAC on Client'),$MAC,$this->obj->clientMacCheck($MAC),_('Ignore MAC for imaging'),$MAC,$this->obj->imageMacCheck($MAC),$MAC);
            unset($MAC);
        }
        $addMACs = ob_get_clean();
        ob_start();
        foreach ((array)$this->obj->get('pendingMACs') AS $i => &$MAC) {
            if (!$MAC->isValid()) continue;
            printf('<div><input class="pending-mac" type="text" name="pendingMACs[]" value="%s"/><a href="%s&confirmMAC=%s"><i class="icon fa fa-check-circle"></i></a><span class="mac-manufactor"></span></div>',$MAC,$this->formAction,$MAC);
            unset($MAC);
        }
        if (ob_get_contents()) {
            printf('<div>%s<a href="%s&approveAll=1"><i class="icon fa fa-check-circle"></i></a></div>',_('Approve All MACs?'),$this->formAction);
        }
        $pending = ob_get_clean();
        $imageSelect = self::getClass('ImageManager')->buildSelectBox($this->obj->get('imageID'));
        $fields = array(
            _('Host Name') => '<input type="text" name="host" value="'.$this->obj->get('name').'" maxlength="15" class="hostname-input" />*',
            _('Primary MAC') => sprintf('<input type="text" name="mac" id="mac" value="%s"/>*<span id="priMaker"></span><i class="icon add-mac fa fa-plus-circle hand" title="%s"></i><span class="icon icon-hand" title="%s"><input type="checkbox" name="igclient[]" value="%s" %s/></span><span class="icon icon-hand" title="%s"><input type="checkbox" name="igimage[]" value="%s" %s/></span><br/><span class="mac-manufactor"></span>',$this->obj->get('mac')->__toString(),_('Add MAC'),_('Ignore MAC on Client'),$this->obj->get('mac')->__toString(),$this->obj->clientMacCheck(),_('Ignore MAC for Imaging'),$this->obj->get('mac')->__toString(),$this->obj->imageMacCheck()),
            sprintf('<div id="additionalMACsRow">%s</div>',_('Additional MACs')) => sprintf('<div id="additionalMACsCell">%s</div>',$addMACs),
            ($this->obj->get('pendingMACs') ? _('Pending MACs') : null) => ($this->obj->get('pendingMACs') ? $pending : null),
            _('Host Description') => sprintf('<textarea name="description" rows="8" cols="40">%s</textarea>',$this->obj->get('description')),
            _('Host Product Key') => sprintf('<input id="productKey" type="text" name="key" value="%s"/>',$this->aesdecrypt($this->obj->get('productKey'))),
            _('Host Image') => $imageSelect,
            _('Host Kernel') => sprintf('<input type="text" name="kern" value="%s"/>',$this->obj->get('kernel')),
            _('Host Kernel Arguments') => sprintf('<input type="text" name="args" value="%s"/>',$this->obj->get('kernelArgs')),
            _('Host Init') => sprintf('<input type="text" name="init" value="%s"/>',$this->obj->get('init')),
            _('Host Primary Disk') => sprintf('<input type="text" name="dev" value="%s"/>',$this->obj->get('kernelDevice')),
            _('Host Bios Exit Type') => $this->exitNorm,
            _('Host EFI Exit Type') => $this->exitEfi,
            ' ' => sprintf('<input type="submit" value="%s"/>',_('Update')),
        );
        self::$HookManager->processEvent('HOST_FIELDS', array('fields' => &$fields,'Host' => &$this->obj));
        echo '<div id="tab-container"><!-- General --><div id="host-general">';
        if ($this->obj->get('pub_key') || $this->obj->get('sec_tok')) $this->form = '<div class="c" id="resetSecDataBox"><input type="button" id="resetSecData"/></div><br/>';
        array_walk($fields,$this->fieldsToData);
        unset($input);
        self::$HookManager->processEvent('HOST_EDIT_GEN',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes,'Host'=>&$this->obj));
        printf('<form method="post" action="%s&tab=host-general"><h2>%s</h2>',$this->formAction,_('Edit host definition'));
        $this->render();
        echo '</form></div>';
        unset($this->data,$this->form);
        unset($this->data,$this->headerData,$this->attributes);
        if (!$this->obj->get('pending')) $this->basictasksOptions();
        $this->adFieldsToDisplay();
        printf('<!-- Printers --><div id="host-printers"><form method="post" action="%s&tab=host-printers">',$this->formAction);
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkboxprint" class="toggle-checkboxprint" />',
            _('Printer Name'),
            _('Configuration'),
        );
        $this->templates = array(
            '<input type="checkbox" name="printer[]" value="${printer_id}" class="toggle-print" />',
            '<a href="?node=printer&sub=edit&id=${printer_id}">${printer_name}</a>',
            '${printer_type}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'l filter-false'),
            array('width'=>50,'class'=>'l'),
            array('width'=>50,'class'=>'r'),
        );
        $printerIterator = function(&$Printer) {
            if (!$Printer->isValid()) return;
            $this->data[] = array(
                'printer_id'=>$Printer->get('id'),
                'is_default'=>($this->obj->getDefault($Printer->get('id')) ? 'checked' : ''),
                'printer_name'=>$Printer->get('name'),
                'printer_type'=>(stripos($Printer->get('config'),'local') !== false ? _('TCP/IP') : $Printer->get('config')),
            );
            unset($Printer);
        };
        array_map($printerIterator,(array)self::getClass('PrinterManager')->find(array('id'=>$this->obj->get('printersnotinme'))));
        $PrintersFound = false;
        if (count($this->data) > 0) {
            $PrintersFound = true;
            self::$HookManager->processEvent('HOST_ADD_PRINTER',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
            printf('<p class="c"><label for="hostPrinterShow">%s&nbsp;&nbsp;<input type="checkbox" name="hostPrinterShow" id="hostPrinterShow"/></label></p><div id="printerNotInHost"><h2>%s</h2>',_('Check here to see what printers can be added'),_('Add new printer(s) to this host'));
            $this->render();
            echo '</div>';
        }
        unset($this->data);
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Default'),
            _('Printer Alias'),
            _('Printer Type'),
        );
        $this->attributes = array(
            array('class'=>'l filter-false','width'=>16),
            array('class'=>'l filter-false','width'=>22),
            array(),
            array(),
        );
        $this->templates = array(
            '<input type="checkbox" name="printerRemove[]" value="${printer_id}" class="toggle-action" />',
            sprintf('<input class="default" type="radio" name="default" id="printer${printer_id}" value="${printer_id}" ${is_default}/><label for="printer${printer_id}" class="icon icon-hand" title="%s">&nbsp;</label><input type="hidden" name="printerid[]" value="${printer_id}"/>',_('Default Printer Select')),
            '<a href="?node=printer&sub=edit&id=${printer_id}">${printer_name}</a>',
            '${printer_type}',
        );
        array_map($printerIterator,(array)self::getClass('PrinterManager')->find(array('id'=>$this->obj->get('printers'))));
        self::$HookManager->processEvent('HOST_EDIT_PRINTER',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        printf('<h2>%s</h2><p>%s</p><p><span class="icon fa fa-question hand" title="%s"></span><input type="radio" name="level" value="0"%s/>%s<br/><span class="icon fa fa-question hand" title="%s"></span><input type="radio" name="level" value="1"%s/>%s<br/><span class="icon fa fa-question hand" title="%s"></span><input type="radio" name="level" value="2"%s/>%s<br/></p>',_('Host Printer Configuration'),_('Select Management Level for this Host'),_('This setting turns off all FOG Printer Management. Although there are multiple levels already between host and global settings, this is just another to ensure safety'),($this->obj->get('printerLevel') == 0 ? ' checked' : ''),_('No Printer Management'),_('This setting only adds and removes printers that are management by FOG. If the printer exists in printer management but is not assigned to a host, it will remove the printer if it exists on the unsigned host. It will add printers to the host that are assigned.'),($this->obj->get('printerLevel') == 1 ? ' checked' : ''),_('FOG Managed Printers'),_('This setting will only allow FOG Assigned printers to be added to the host. Any printer that is not assigned will be removed including non-FOG managed printers.'),($this->obj->get('printerLevel') == 2 ? ' checked': ''),_('Only Assigned Printers'));
        $this->render();
        if ($PrintersFound || count($this->data) > 0) printf('<p class="c"><input type="submit" value="%s" name="updateprinters"/>',_('Update'));
        if (count($this->data) > 0) printf('&nbsp;&nbsp;<input type="submit" value="%s" name="printdel"/></p>',_('Remove selected printers'));
        unset($this->data, $this->headerData);
        echo '</form></div>';
        printf('<!-- Snapins --><div id="host-snapins"><h2>%s</h2><form method="post" action="%s&tab=host-snapins">',_('Snapins'),$this->formAction);
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkboxsnapin" class="toggle-checkboxsnapin"/>',
            _('Snapin Name'),
            _('Created'),
        );
        $this->templates = array(
            '<input type="checkbox" name="snapin[]" value="${snapin_id}" class="toggle-snapin"/>',
            sprintf('<a href="?node=%s&sub=edit&id=${snapin_id}" title="%s">${snapin_name}</a>','snapin',_('Edit')),
            '${snapin_created}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'l filter-false'),
            array('width'=>90,'class'=>'l'),
            array('width'=>20,'class'=>'r'),
        );
        foreach ((array)self::getClass('SnapinManager')->find(array('id'=>$this->obj->get('snapinsnotinme'))) AS $i => &$Snapin) {
            if (!$Snapin->isValid()) continue;
            $this->data[] = array(
                'snapin_id'=>$Snapin->get('id'),
                'snapin_name'=>$Snapin->get('name'),
                'snapin_created'=>$Snapin->get('createdTime'),
            );
            unset($Snapin);
        }
        if (count($this->data) > 0) {
            printf('<p class="c"><label for="hostSnapinShow">%s&nbsp;&nbsp;<input type="checkbox" name="hostSnapinShow" id="hostSnapinShow"/></label><div id="snapinNotInHost">',_('Check here to see what snapins can be added'));
            self::$HookManager->processEvent('HOST_SNAPIN_JOIN',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
            $this->render();
            printf('<input type="submit" value="%s"/></form></div></p><form method="post" action="%s&tab=host-snapins">',_('Add Snapin(s)'),$this->formAction);
            unset($this->data);
        }
        $this->headerData = array(
            '<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction"/>',
            _('Snapin Name'),
        );
        $this->attributes = array(
            array('class'=>'l filter-false','width'=>16),
            array(),
        );
        $this->templates = array(
            '<input type="checkbox" name="snapinRemove[]" value="${snap_id}" class="toggle-action"/>',
            '<a href="?node=snapin&sub=edit&id=${snap_id}">${snap_name}</a>',
        );
        foreach ((array)self::getClass('SnapinManager')->find(array('id'=>$this->obj->get('snapins'))) AS $i => &$Snapin) {
            if (!$Snapin->isValid()) continue;
            $this->data[] = array(
                'snap_id'=>$Snapin->get('id'),
                'snap_name'=>$Snapin->get('name'),
            );
            unset($Snapin);
        }
        self::$HookManager->processEvent('HOST_EDIT_SNAPIN',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        if (count($this->data)) $inputremove = sprintf('<input type="submit" name="snaprem" value="%s"/>',_('Remove selected snapins'));
        echo "<p class='c'>$inputremove</p></form></div>";
        unset($this->data,$this->headerData);
        echo '<!-- Service Configuration -->';
        $this->attributes = array(
            array('width'=>270),
            array('class'=>'c'),
            array('class'=>'r'),
        );
        $this->templates = array(
            '${mod_name}',
            '${input}',
            '${span}',
        );
        $this->data[] = array(
            'mod_name'=>_('Select/Deselect All'),
            'input'=>'<input type="checkbox" class="checkboxes" id="checkAll" name="checkAll" value="checkAll"/>',
            'span'=>''
        );
        printf('<div id="host-service"><h2>%s</h2><form method="post" action="%s&tab=host-service"><fieldset><legend>%s</legend>',_('Service Configuration'),$this->formAction,_('General'));
        $moduleName = $this->getGlobalModuleStatus();
        $ModuleOn = array_values(self::getSubObjectIDs('ModuleAssociation',array('hostID'=>$this->obj->get('id')),'moduleID',false,'AND','id',false,''));
        array_map(function(&$Module) use ($moduleName,$ModuleOn) {
            if (!$Module->isValid()) return;
            $this->data[] = array(
                'input' => sprintf('<input %stype="checkbox" name="modules[]" value="%s"%s%s/>',($moduleName[$Module->get('shortName')] || ($moduleName[$Module->get('shortName')] && $Module->get('isDefault')) ? 'class="checkboxes" ': ''), $Module->get('id'),(in_array($Module->get('id'),$ModuleOn) ? ' checked' : ''),!$moduleName[$Module->get('shortName')] ? ' disabled' : ''),
                'span'=>sprintf('<span class="icon fa fa-question fa-1x hand" title="%s"></span>',str_replace('"','\"',$Module->get('description'))),
                'mod_name'=>$Module->get('name'),
            );
            unset($Module);
        },(array)self::getClass('ModuleManager')->find());
        unset($moduleName,$ModuleOn);
        $this->data[] = array(
            'mod_name'=>'',
            'input'=>'',
            'span'=>sprintf('<input type="submit" name="updatestatus" value="%s"/>',_('Update')),
        );
        self::$HookManager->processEvent('HOST_EDIT_SERVICE',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data);
        printf('</fieldset><fieldset><legend>%s</legend>',_('Host Screen Resolution'));
        $this->attributes = array(
            array('class'=>'l','style'=>'padding-right: 25px'),
            array('class'=>'c'),
            array('class'=>'r'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${span}',
        );
        array_map(function(&$Service) {
            if (!$Service->isValid()) return;
            switch ($Service->get('name')) {
            case 'FOG_CLIENT_DISPLAYMANAGER_X':
                $name = 'x';
                $field = _('Screen Width (in pixels)');
                break;
            case 'FOG_CLIENT_DISPLAYMANAGER_Y':
                $name = 'y';
                $field = _('Screen Height (in pixels)');
                break;
            case 'FOG_CLIENT_DISPLAYMANAGER_R':
                $name = 'r';
                $field = _('Screen Refresh Rate (in Hz)');
                break;
            }
            $this->data[] = array(
                'input'=>sprintf('<input type="text" name="%s" value="%s"/>',$name,$Service->get('value')),
                'span'=>sprintf('<span class="icon fa fa-question fa-1x hand" title="%s"></span>',$Service->get('description')),
                'field'=>$field,
            );
            unset($name,$field,$Service);
        },self::getClass('ServiceManager')->find(array('name'=>array('FOG_CLIENT_DISPLAYMANAGER_X','FOG_CLIENT_DISPLAYMANAGER_Y','FOG_CLIENT_DISPLAYMANAGER_R')),'OR','id'));
        $this->data[] = array(
            'field'=>'',
            'input'=>'',
            'span'=>sprintf('<input type="submit" name="updatedisplay" value="%s"/>',_('Update')),
        );
        self::$HookManager->processEvent('HOST_EDIT_DISPSERV',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data);
        printf('</fieldset><fieldset><legend>%s</legend>',_('Auto Log Out Settings'));
        $this->attributes = array(
            array('width'=>270),
            array('class'=>'c'),
            array('class'=>'r'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${desc}',
        );
        $Service = self::getClass('Service',@min(self::getSubObjectIDs('Service',array('name'=>'FOG_CLIENT_AUTOLOGOFF_MIN'))));
        if ($Service->isValid()) {
            $this->data[] = array(
                'field'=>_('Auto Log Out Time (in minutes)'),
                'input'=>'<input type="text" name="tme" value="${value}"/>',
                'desc'=>'<span class="icon fa fa-question fa-1x hand" title="${serv_desc}"></span>',
                'value'=>$this->obj->getAlo() ? $this->obj->getAlo() : $Service->get('value'),
                'serv_desc'=>$Service->get('description'),
            );
        }
        unset($Service);
        $this->data[] = array(
            'field'=>'',
            'input'=>'',
            'desc'=> sprintf('<input type="submit" name="updatealo" value="%s"/>',_('Update')),
        );
        self::$HookManager->processEvent('HOST_EDIT_ALO',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data,$fields);
        echo '</fieldset></form></div>';
        echo '<!-- Power Management Items --><div id="host-powermanagement"><p id="cronOptions">';
        $this->headerData = array(
            '<input type="checkbox" id="rempowerselectors"/>',
            _('Cron Schedule'),
            _('Action'),
        );
        $this->templates = array(
            '<input type="checkbox" name="rempowermanagements[]" class="rempoweritems" value="${id}"/>',
            '<div class="deploy-container" class="l"><p id="cronOptions"><input type="hidden" name="pmid[]" value="${id}"/><input type="text" name="scheduleCronMin[]" id="scheduleCronMin" autocomplete="off" value="${min}"/><input type="text" name="scheduleCronHour[]" id="scheduleCronHour" autocomplete="off" value="${hour}"/><input type="text" name="scheduleCronDOM[]" id="scheduleCronDOM" autocomplete="off" value="${dom}"/><input type="text" name="scheduleCronMonth[]" id="scheduleCronMonth" autocomplete="off" value="${month}"/><input type="text" name="scheduleCronDOW[]" id="scheduleCronDOW" autocomplete="off" value="${dow}"/></p></div>',
            '${action}',
        );
        $this->attributes = array(
            array('width'=>16,'class'=>'l filter-false'),
            array('class'=>'filter-false'),
            array('class'=>'filter-false'),
        );
        array_map(function(&$PowerManagement) {
            if (!$PowerManagement->isValid()) return;
            if ($PowerManagement->get('onDemand')) return;
            $this->data[] = array(
                'id' => $PowerManagement->get('id'),
                'min' => $PowerManagement->get('min'),
                'hour' => $PowerManagement->get('hour'),
                'dom' => $PowerManagement->get('dom'),
                'month' => $PowerManagement->get('month'),
                'dow' => $PowerManagement->get('dow'),
                'is_selected' => $PowerManagement->get('action') ? ' selected' : '',
                'action' => $PowerManagement->getActionSelect(),
            );
        },(array)self::getClass('PowerManagementManager')->find(array('id'=>$this->obj->get('powermanagementtasks'))));
        if (count($this->data) > 0) {
            printf('<form method="post" action="%s&tab=host-powermanagement" class="deploy-container">',$this->formAction);
            $this->render();
            printf('<center><input type="submit" name="pmupdate" value="%s"/>&nbsp;<input type="submit" name="pmdelete" value="%s"/></center><br/>',_('Update Values'),_('Remove selected'));
            echo '</form>';
        }
        unset($this->headerData,$this->templates,$this->attributes,$this->data);
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $this->attributes = array(
            array(),
            array(),
        );
        $fields = array(
            _('Schedule Power') => sprintf('<p id="cronOptions"><input type="text" name="scheduleCronMin" id="scheduleCronMin" placeholder="min" autocomplete="off" value="%s"/><input type="text" name="scheduleCronHour" id="scheduleCronHour" placeholder="hour" autocomplete="off" value="%s"/><input type="text" name="scheduleCronDOM" id="scheduleCronDOM" placeholder="dom" autocomplete="off" value="%s"/><input type="text" name="scheduleCronMonth" id="scheduleCronMonth" placeholder="month" autocomplete="off" value="%s"/><input type="text" name="scheduleCronDOW" id="scheduleCronDOW" placeholder="dow" autocomplete="off" value="%s"/></p>',$_REQUEST['scheduleCronMin'],$_REQUEST['scheduleCronHour'],$_REQUEST['scheduleCronDOM'],$_REQUEST['scheduleCronMonth'],$_REQUEST['scheduleCronDOW']),
            _('Perform Immediately?') => sprintf('<input type="checkbox" name="onDemand" id="scheduleOnDemand"%s/>',!is_array($_REQUEST['onDemand']) && isset($_REQUEST['onDemand']) ? ' checked' : ''),
            _('Action') => self::getClass('PowerManagementManager')->getActionSelect($_REQUEST['action']),
        );
        array_walk($fields,function(&$input,&$field) {
            $this->data[] = array(
                'field' => $field,
                'input' => $input,
            );
        });
        printf('<form method="post" action="%s&tab=host-powermanagement" class="deploy-container">',$this->formAction);
        $this->render();
        printf('<center><input type="submit" name="pmsubmit" value="%s"/></center></form></div>',_('Add Option'));
        unset($this->headerData,$this->templates,$this->data,$this->attributes);
        echo '<!-- Inventory -->';
        $this->attributes = array(
            array(),
            array(),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        array_map(function(&$x) {
            $this->obj->get('inventory')->set($x,implode(' ',array_unique(explode(' ',$this->obj->get('inventory')->get($x)))));
            unset($x);
        }, array('cpuman','cpuversion'));
        $fields = array(
            _('Primary User') => sprintf('<input type="text" value="%s" name="pu"/>',$this->obj->get('inventory')->get('primaryUser')),
            _('Other Tag #1') => sprintf('<input type="text" value="%s" name="other1"/>',$this->obj->get('inventory')->get('other1')),
            _('Other Tag #2') => sprintf('<input type="text" value="%s" name="other2"/>',$this->obj->get('inventory')->get('other2')),
            _('System Manufacturer') => $this->obj->get('inventory')->get('sysman'),
            _('System Product') => $this->obj->get('inventory')->get('sysproduct'),
            _('System Version') => $this->obj->get('inventory')->get('sysversion'),
            _('System Serial Number') => $this->obj->get('inventory')->get('sysserial'),
            _('System Type') => $this->obj->get('inventory')->get('systype'),
            _('BIOS Vendor') => $this->obj->get('inventory')->get('biosvendor'),
            _('BIOS Version') => $this->obj->get('inventory')->get('biosversion'),
            _('BIOS Date') => $this->obj->get('inventory')->get('biosdate'),
            _('Motherboard Manufacturer') => $this->obj->get('inventory')->get('mbman'),
            _('Motherboard Product Name') => $this->obj->get('inventory')->get('mbproductname'),
            _('Motherboard Version') => $this->obj->get('inventory')->get('mbversion'),
            _('Motherboard Serial Number') => $this->obj->get('inventory')->get('mbserial'),
            _('Motherboard Asset Tag') => $this->obj->get('inventory')->get('mbasset'),
            _('CPU Manufacturer') => $this->obj->get('inventory')->get('cpuman'),
            _('CPU Version') => $this->obj->get('inventory')->get('cpuversion'),
            _('CPU Normal Speed') => $this->obj->get('inventory')->get('cpucurrent'),
            _('CPU Max Speed') => $this->obj->get('inventory')->get('cpumax'),
            _('Memory') => $this->obj->get('inventory')->getMem(),
            _('Hard Disk Model') => $this->obj->get('inventory')->get('hdmodel'),
            _('Hard Disk Firmware') => $this->obj->get('inventory')->get('hdfirmware'),
            _('Hard Disk Serial Number') => $this->obj->get('inventory')->get('hdserial'),
            _('Chassis Manufacturer') => $this->obj->get('inventory')->get('caseman'),
            _('Chassis Version') => $this->obj->get('inventory')->get('caseversion'),
            _('Chassis Serial') => $this->obj->get('inventory')->get('caseserial'),
            _('Chassis Asset') => $this->obj->get('inventory')->get('caseasset'),
            ' ' => sprintf('<input name="update" type="submit" value="%s"/>',_('Update')),
        );
        printf('<div id="host-hardware-inventory"><form method="post" action="%s&tab=host-hardware-inventory"><h2>%s</h2>',$this->formAction,_('Host Hardware Inventory'));
        if ($this->obj->get('inventory')->isValid()) array_walk($fields,$this->fieldsToData);
        self::$HookManager->processEvent('HOST_INVENTORY',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data,$fields);
        echo '</form></div><!-- Virus -->';
        $this->headerData = array(
            _('Virus Name'),
            _('File'),
            _('Mode'),
            _('Date'),
            _('Clear'),
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '<a href="http://www.google.com/search?q=${virus_name}" target="_blank">${virus_name}</a>',
            '${virus_file}',
            '${virus_mode}',
            '${virus_date}',
            sprintf('<input type="checkbox" id="vir_del${virus_id}" class="delvid" name="delvid" onclick="this.form.submit()" value="${virus_id}"/><label for="${virus_id}" class="icon icon-hand" title="%s ${virus_name}"><i class="icon fa fa-minus-circle link"></i></label>',_('Delete')),
        );
        printf('<div id="host-virus-history"><form method="post" action="%s&tab=host-virus-history"><h2>%s</h2><h2><a href="#"><input type="checkbox" class="delvid" id="all" name="delvid" value="all" onclick="this.form.submit()"/><label for="all">(%s)</label></a></h2>',$this->formAction,_('Virus History'),_('clear all history'));
        foreach ((array)self::getClass('VirusManager')->find(array('hostMAC'=>$this->obj->getMyMacs()),'OR') AS $i => &$Virus) {
            if (!$Virus->isValid()) continue;
            $this->data[] = array(
                'virus_name'=>$Virus->get('name'),
                'virus_file'=>$Virus->get('file'),
                'virus_mode'=>($Virus->get('mode') == 'q' ? _('Quarantine') : ($Virus->get('mode') == 's' ? _('Report') : 'N/A')),
                'virus_date'=>$Virus->get('date'),
                'virus_id'=>$Virus->get('id'),
            );
            unset($Virus);
        }
        self::$HookManager->processEvent('HOST_VIRUS',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data,$this->headerData);
        printf('</form></div><!-- Login History --><div id="host-login-history" ><h2>%s</h2><form id="dte" method="post" action="%s&tab=host-login-history">',_('Host Login History'),$this->formAction);
        $this->headerData = array(
            _('Time'),
            _('Action'),
            _('Username'),
            _('Description')
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '${user_time}',
            '${action}',
            '${user_name}',
            '${user_desc}',
        );
        $Dates = array_unique((array)self::getSubObjectIDs('UserTracking',array('id'=>$this->obj->get('users')),'date'));
        if ($Dates) {
            rsort($Dates);
            printf('<p>%s</p>',_('View History for'));
            ob_start();
            foreach ((array)$Dates AS $i => &$Date) {
                if ($_REQUEST['dte'] == '') $_REQUEST['dte'] = $Date;
                printf('<option value="%s"%s>%s</option>',$Date,($Date == $_REQUEST['dte'] ? ' selected' : ''),$Date);
                unset($Date);
            }
            unset($Dates);
            printf('<select name="dte" id="loghist-date" size="1" onchange="document.getElementById(\'dte\').submit()">%s</select><a href="#" onclick="document.getElementByID(\'dte\').submit()"><i class="icon fa fa-play noBorder"></i></a></p>',ob_get_clean());
            foreach ((array)self::getClass('UserTrackingManager')->find(array('id'=>$this->obj->get('users'))) AS $i => &$UserLogin) {
                if (!$UserLogin->isValid()) continue;
                if ($UserLogin->get('date') == $_REQUEST['dte']) {
                    $this->data[] = array(
                        'action'=>($UserLogin->get('action') == 1 ? _('Login') : ($UserLogin->get('action') == 0 ? _('Logout') : '')),
                        'user_name'=>$UserLogin->get('username'),
                        'user_time'=>$UserLogin->get('datetime'),
                        'user_desc'=>$UserLogin->get('description'),
                    );
                }
                unset($UserLogin);
            }
            self::$HookManager->processEvent('HOST_USER_LOGIN',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
            $this->render();
        } else printf('<p>%s</p>',_('No user history data found!'));
        unset($this->data,$this->headerData);
        printf('<div id="login-history" style="width:575px;height:200px;"/></div></form></div><div id="host-image-history"><h2>%s</h2>',_('Host Imaging History'));
        $this->headerData = array(
            _('Image Name'),
            _('Imaging Type'),
            sprintf('<small>%s</small><br/>%s',_('Completed'),_('Duration')),
        );
        $this->templates = array(
            '${image_name}',
            '${image_type}',
            '<small>${completed}</small><br/>${duration}',
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
        );
        foreach ((array)self::getClass('ImagingLogManager')->find(array('hostID'=>$this->obj->get('id'))) AS $i => &$ImageLog) {
            if (!$ImageLog->isValid()) continue;
            $Start = $ImageLog->get('start');
            $End = $ImageLog->get('finish');
            $this->data[] = array(
                'completed'=>$this->formatTime($End,'Y-m-d H:i:s'),
                'duration'=>$this->diff($Start,$End),
                'image_name'=>$ImageLog->get('image'),
                'image_type'=>$ImageLog->get('type'),
            );
            unset($ImageLog,$Start,$End);
        }
        self::$HookManager->processEvent('HOST_IMAGE_HIST',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        unset($this->data);
        echo '</div><div id="host-snapin-history">';
        $this->headerData = array(
            _('Snapin Name'),
            _('Start Time'),
            _('Complete'),
            _('Duration'),
            _('Return Code'),
        );
        $this->templates = array(
            '${snapin_name}',
            '${snapin_start}',
            '${snapin_end}',
            '${snapin_duration}',
            '${snapin_return}',
        );
        $SnapinJobIDs = self::getSubObjectIDs('SnapinJob',array('hostID'=>$this->obj->get('id')));
        foreach (self::getClass('SnapinTaskManager')->find(array('jobID'=>$SnapinJobIDs)) AS &$SnapinTask) {
            if (!$SnapinTask->isValid()) continue;
            $Snapin = $SnapinTask->getSnapin();
            if (!$Snapin->isValid()) continue;
            $this->data[] = array(
                'snapin_name' => $Snapin->get('name'),
                'snapin_start' => $this->formatTime($SnapinTask->get('checkin'),'Y-m-d H:i:s'),
                'snapin_end' => sprintf('<span class="icon" title="%s">%s</span>',$this->formatTime($SnapinTask->get('complete'),'Y-m-d H:i:s'),self::getClass('TaskState',$SnapinTask->get('stateID'))->get('name')),
                'snapin_duration' => $this->diff($SnapinTask->get('checkin'),$SnapinTask->get('complete')),
                'snapin_return'=> $SnapinTask->get('return'),
            );
            unset($Snapin,$SnapinTask);
        }
        self::$HookManager->processEvent('HOST_SNAPIN_HIST',array('headerData'=>&$this->headerData,'data'=>&$this->data,'templates'=>&$this->templates,'attributes'=>&$this->attributes));
        $this->render();
        echo '</div></div>';
    }
    public function edit_ajax() {
        //$this->obj->removeAddMAC($_REQUEST['additionalMACsRM'])->save();
        //echo _('Success');
        exit;
    }
    public function edit_post() {
        self::$HookManager->processEvent('HOST_EDIT_POST',array('Host'=>&$this->obj));
        try {
            switch ($_REQUEST['tab']) {
            case 'host-general':
                $hostName = trim($_REQUEST['host']);
                if (empty($hostName)) throw new Exception('Please enter a hostname');
                if ($this->obj->get('name') != $hostName && !$this->obj->isHostnameSafe($hostName)) throw new Exception(_('Please enter a valid hostname'));
                if ($this->obj->get('name') != $hostName && $this->obj->getManager()->exists($hostName)) throw new Exception('Hostname Exists already');
                if (empty($_REQUEST['mac'])) throw new Exception('MAC Address is required');
                $mac = self::getClass('MACAddress',$_REQUEST['mac']);
                $Task = $this->obj->get('task');
                if (!$mac->isValid()) throw new Exception(_('MAC Address is not valid'));
                if ((!$_REQUEST['image'] && $Task->isValid()) || ($_REQUEST['image'] && $_REQUEST['image'] != $this->obj->get('imageID') && $Task->isValid())) throw new Exception('Cannot unset image.<br />Host is currently in a tasking.');
                $productKey = preg_replace('/([\w+]{5})/','$1-',str_replace('-','',strtoupper(trim($_REQUEST['key']))));
                $productKey = substr($productKey,0,29);
                $this->obj
                    ->set('name',$hostName)
                    ->set('description',$_REQUEST['description'])
                    ->set('imageID',$_REQUEST['image'])
                    ->set('kernel',$_REQUEST['kern'])
                    ->set('kernelArgs',$_REQUEST['args'])
                    ->set('kernelDevice',$_REQUEST['dev'])
                    ->set('init',$_REQUEST['init'])
                    ->set('biosexit',$_REQUEST['bootTypeExit'])
                    ->set('efiexit',$_REQUEST['efiBootTypeExit'])
                    ->set('productKey',$this->encryptpw($productKey));
                if (strtolower($this->obj->get('mac')->__toString()) != strtolower($mac->__toString())) $this->obj->addPriMAC($mac->__toString());
                $_REQUEST['additionalMACs'] = array_map('strtolower',(array)$_REQUEST['additionalMACs']);
                $removeMACs = array_diff((array)self::getSubObjectIDs('MACAddressAssociation',array('hostID'=>$this->obj->get('id'),'primary'=>array((string)0,(string)'',null),'pending'=>array((string)0,(string)'',null)),'mac'),(array)$_REQUEST['additionalMACs']);
                $this->obj->addAddMAC($_REQUEST['additionalMACs'])
                    ->removeAddMAC($removeMACs);
                break;
            case 'host-active-directory':
                $useAD = isset($_REQUEST['domain']);
                $domain = trim($_REQUEST['domainname']);
                $ou = trim($_REQUEST['ou']);
                $user = trim($_REQUEST['domainuser']);
                $pass = trim($_REQUEST['domainpassword']);
                $passlegacy = trim($_REQUEST['domainpasswordlegacy']);
                $enforce = (string)intval(isset($_REQUEST['enforcesel']));
                $this->obj->setAD($useAD,$domain,$ou,$user,$pass,true,$passlegacy,$productKey,$enforce);
                break;
            case 'host-powermanagement':
                $min = $_REQUEST['scheduleCronMin'];
                $hour = $_REQUEST['scheduleCronHour'];
                $dom = $_REQUEST['scheduleCronDOM'];
                $month = $_REQUEST['scheduleCronMonth'];
                $dow = $_REQUEST['scheduleCronDOW'];
                $onDemand = (string)intval(isset($_REQUEST['onDemand']));
                $action = $_REQUEST['action'];
                if (!$action) throw new Exception(_('You must select an action to perform'));
                $items = array();
                if (isset($_REQUEST['pmupdate'])) {
                    $pmid = $_REQUEST['pmid'];
                    array_walk($pmid,function(&$pm,&$index) use (&$min,&$hour,&$dom,&$month,&$dow,&$onDemand,&$action,&$items) {
                        $onDemandItem = array_search($pm,$onDemand);
                        $items[] = array($pm,$this->obj->get('id'),$min[$index],$hour[$index],$dom[$index],$month[$index],$dow[$index],$onDemandItem !== -1 && $onDemand[$onDemandItem] === $pm ? '1' : '0',$action[$index]);
                    });
                    self::getClass('PowerManagementManager')->insert_batch(array('id','hostID','min','hour','dom','month','dow','onDemand','action'),$items);
                }
                if (isset($_REQUEST['pmsubmit'])) {
                    if ($onDemand && $action === 'wol'){
                        $this->obj->wakeOnLAN();
                        break;
                    }
                    self::getClass('PowerManagement')
                        ->set('hostID',$this->obj->get('id'))
                        ->set('min',$min)
                        ->set('hour',$hour)
                        ->set('dom',$dom)
                        ->set('month',$month)
                        ->set('dow',$dow)
                        ->set('onDemand',$onDemand)
                        ->set('action',$action)
                        ->save();
                }
                if (isset($_REQUEST['pmdelete'])) self::getClass('PowerManagementManager')->destroy(array('id'=>$_REQUEST['rempowermanagements']));
                break;
            case 'host-printers':
                $PrinterManager = self::getClass('PrinterAssociationManager');
                if (isset($_REQUEST['level'])) $this->obj->set('printerLevel',$_REQUEST['level']);
                if (isset($_REQUEST['updateprinters'])) {
                    if (isset($_REQUEST['printer'])) $this->obj->addPrinter($_REQUEST['printer']);
                    $this->obj->updateDefault($_REQUEST['default'],isset($_REQUEST['default']));
                    unset($printerid);
                }
                if (isset($_REQUEST['printdel'])) $this->obj->removePrinter($_REQUEST['printerRemove']);
                break;
            case 'host-snapins':
                if (!isset($_REQUEST['snapinRemove'])) $this->obj->addSnapin($_REQUEST['snapin']);
                if (isset($_REQUEST['snaprem'])) $this->obj->removeSnapin($_REQUEST['snapinRemove']);
                break;
            case 'host-service':
                $x =(is_numeric($_REQUEST['x']) ? $_REQUEST['x'] : self::getSetting('FOG_CLIENT_DISPLAYMANAGER_X'));
                $y =(is_numeric($_REQUEST['y']) ? $_REQUEST['y'] : self::getSetting('FOG_CLIENT_DISPLAYMANAGER_Y'));
                $r =(is_numeric($_REQUEST['r']) ? $_REQUEST['r'] : self::getSetting('FOG_CLIENT_DISPLAYMANAGER_R'));
                $tme = (is_numeric($_REQUEST['tme']) ? $_REQUEST['tme'] : self::getSetting('FOG_CLIENT_AUTOLOGOFF_MIN'));
                if (isset($_REQUEST['updatestatus'])) {
                    $modOn = (array)$_REQUEST['modules'];
                    $modOff = self::getSubObjectIDs('Module',array('id'=>$modOn),'id',true);
                    $this->obj->addModule($modOn);
                    $this->obj->removeModule($modOff);
                }
                if (isset($_REQUEST['updatedisplay'])) $this->obj->setDisp($x,$y,$r);
                if (isset($_REQUEST['updatealo'])) $this->obj->setAlo($tme);
                break;
            case 'host-hardware-inventory':
                $pu = trim($_REQUEST['pu']);
                $other1 = trim($_REQUEST['other1']);
                $other2 = trim($_REQUEST['other2']);
                if (isset($_REQUEST['update'])) {
                    $this->obj
                        ->get('inventory')
                        ->set('primaryUser',$pu)
                        ->set('other1',$other1)
                        ->set('other2',$other2)
                        ->save();
                }
                break;
            case 'host-login-history':
                $this->redirect(sprintf('?node=host&sub=edit&id=%s&dte=%s#%s',$this->obj->get('id'),$_REQUEST['dte'],$_REQUEST['tab']));
                break;
            case 'host-virus-history':
                if (isset($_REQUEST['delvid']) && $_REQUEST['delvid'] == 'all') {
                    $this->obj->clearAVRecordsForHost();
                    $this->redirect(sprintf('?node=host&sub=edit&id=%s#%s',$this->obj->get('id'),$_REQUEST['tab']));
                } else if (isset($_REQUEST['delvid'])) self::getClass('VirusManager')->destroy(array('id' => $_REQUEST['delvid']));
                break;
            }
            if (!$this->obj->save()) throw new Exception(_('Host Update Failed'));
            if ($_REQUEST['tab'] == 'host-general') $this->obj->ignore($_REQUEST['igimage'],$_REQUEST['igclient']);
            self::$HookManager->processEvent('HOST_EDIT_SUCCESS',array('Host'=>&$this->obj));
            $this->setMessage('Host updated!');
        } catch (Exception $e) {
            self::$HookManager->processEvent('HOST_EDIT_FAIL',array('Host'=>&$this->obj));
            $this->setMessage($e->getMessage());
        }
        $this->redirect(sprintf('%s#%s',$this->formAction,$_REQUEST['tab']));
    }
    public function save_group() {
        try {
            if (empty($_REQUEST['hostIDArray'])) throw new Exception(_('No Hosts were selected'));
            if (empty($_REQUEST['group_new']) && empty($_REQUEST['group'])) throw new Exception(_('No Group selected and no new Group name entered'));
            if (!empty($_REQUEST['group_new'])) {
                $Group = self::getClass('Group')
                    ->set('name',$_REQUEST['group_new']);
                if (!$Group->save()) throw new Exception(_('Failed to create new Group'));
            } else $Group = self::getClass('Group',$_REQUEST['group']);
            if (!$Group->isValid()) throw new Exception(_('Group is Invalid'));
            $Group->addHost(explode(',',$_REQUEST['hostIDArray']))->save();
            printf('<div class="task-start-ok"><p>%s</p></div>',_('Successfully associated Hosts with the Group '));
        } catch (Exception $e) {
            printf('<div class="task-start-failed"><p>%s</p><p>%s</p></div>', _('Failed to Associate Hosts with Group'), $e->getMessage());
        }
    }
    public function hostlogins() {
        $MainDate = self::nice_date($_REQUEST['dte'])->getTimestamp();
        $MainDate_1 = self::nice_date($_REQUEST['dte'])->modify('+1 day')->getTimestamp();
        foreach ((array)self::getClass('UserTrackingManager')->find(array('hostID'=>$this->obj->get('id'),'date'=>$_REQUEST['dte'],'action'=>array(null,0,1)),'AND','date','DESC') AS $i => &$Login) {
            if (!$Login->isValid()) continue;
            if ($Login->get('username') == 'Array') continue;
            $time = self::nice_date($Login->get('datetime'))->format('U');
            if (!$Data[$Login->get('username')]) $Data[$Login->get('username')] = array('user'=>$Login->get('username'),'min'=>$MainDate,'max'=>$MainDate_1);
            if ($Login->get('action')) $Data[$Login->get('username')]['login'] = $time;
            if (array_key_exists('login',$Data[$Login->get('username')]) && !$Login->get('action')) $Data[$Login->get('username')]['logout'] = $time;
            if (array_key_exists('login',$Data[$Login->get('username')]) && array_key_exists('logout',$Data[$Login->get('username')])) {
                $data[] = $Data[$Login->get('username')];
                unset($Data[$Login->get('username')]);
            }
            unset($Login);
        }
        unset($Users);
        echo json_encode($data);
        exit;
    }
}
