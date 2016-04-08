<?php
class ProcessLogin extends FOGBase {
    private $username, $password, $currentUser, $langSet;
    private $mobileMenu, $langMenu;
    private $lang;
    private function getLanguages() {
        $translang = $this->transLang();
        ob_start();
        foreach ((array)static::$foglang['Language'] AS $i => &$lang) {
            printf('<option value="%s"%s>%s</option>',
                $lang,
                ($translang == $lang ? ' selected' : ''),
                $lang
            );
            unset($lang);
        }
        $this->langMenu = ob_get_clean();
    }
    private function defaultLang() {
        $deflang = $this->getSetting('FOG_DEFAULT_LOCALE');
        foreach(static::$foglang['Language'] AS $lang => &$val) {
            if ($deflang == $lang) $data = array($lang,$val);
            else $data = array('en','English');
        }
        unset($val);
        return $data;
    }
    private function transLang() {
        switch($_SESSION['locale']) {
        case 'en_US.UTF-8':
            return static::$foglang['Language']['en'];
            break;
        case 'it_IT.UTF-8':
            return static::$foglang['Language']['it'];
            break;
        case 'es_ES.UTF-8':
            return static::$foglang['Language']['es'];
            break;
        case 'fr_FR.UTF-8':
            return static::$foglang['Language']['fr'];
            break;
        case 'zh_CN.UTF-8':
            return static::$foglang['Language']['zh'];
            break;
        case 'de_DE.UTF-8':
            return static::$foglang['Language']['de'];
            break;
        case 'pt_BR.UTF-8':
            return static::$foglang['Language']['pt'];
            break;
        default :
            $lang = $this->defaultLang();
            return static::$foglang['Language'][$lang[0]];
            break;
        }
    }
    private function specLang() {
        switch ($this->lang[1]) {
        case static::$foglang['Language']['en']:
            $this->lang = 'en_US.UTF-8';
            break;
        case static::$foglang['Language']['fr']:
            $this->lang = 'fr_FR.UTF-8';
            break;
        case static::$foglang['Language']['it']:
            $this->lang = 'it_IT.UTF-8';
            break;
        case static::$foglang['Language']['zh']:
            $this->lang = 'zh_CN.UTF-8';
            break;
        case static::$foglang['Language']['es']:
            $this->lang = 'es_ES.UTF-8';
            break;
        case static::$foglang['Language']['de']:
            $this->lang = 'de_DE.UTF-8';
            break;
        case static::$foglang['Language']['pt']:
            $this->lang = 'pt_BR.UTF-8';
            break;
        default :
            $this->lang = $this->defaultLang();
            $this->specLang();
            break;
        }
    }
    public function setLang() {
        $langs = array(
            'en_US.UTF-8' => true,
            'fr_FR.UTF-8' => true,
            'it_IT.UTF-8' => true,
            'zh_CN.UTF-8' => true,
            'es_ES.UTF-8' => true,
            'de_DE.UTF-8' => true,
            'pt_BR.UTF-8' => true,
        );
        if (!isset($this->lang)) {
            $this->lang = $this->defaultLang();
            $this->specLang();
        }
        if (!isset($langs[$this->lang])) die('Invalid language specification');
        $this->specLang();
        $_SESSION['locale'] = $this->lang;
        setlocale(LC_ALL,$_SESSION['locale']);
        bindtextdomain('messages','languages');
        textdomain('messages');
    }
    private function setCurUser($tmpUser) {
        $this->setRedirMode();
        $this->currentUser = $tmpUser;
        if (!static::$isMobile) static::$HookManager->processEvent('LoginSuccess',array('user'=>&$this->currentUser,'username'=>$this->username, 'password'=>&$this->password));
    }
    private function setRedirMode() {
        foreach ($_REQUEST AS $key => &$value) $redirect[$key] = htmlentities($value,ENT_QUOTES,'utf-8');
        unset($redirect['upass'],$redirect['uname'],$redirect['ulang']);
        if (in_array($redirect['node'],array('login','logout'))) unset($redirect['node']);
        foreach ((array)$redirect AS $key => $value) {
            if (!$value) continue;
            $http_query[$key] = $value;
            unset($value);
        }
        if (count($http_query) < 1) $this->redirect('index.php');
        $this->redirect(sprintf('%s?%s','index.php',http_build_query($http_query)));
    }
    public function processMainLogin() {
        if (!$_SESSION['locale']) $this->setLang();
        if (!(isset($_REQUEST['uname']) && isset($_REQUEST['upass']))) return;
        $this->username = trim($_REQUEST['uname']);
        $this->password = trim($_REQUEST['upass']);
        $tmpUser = static::$FOGCore->attemptLogin($this->username,$this->password);
        if (!$tmpUser || !$tmpUser->isValid()) return;
        static::$HookManager->processEvent('USER_LOGGING_IN',array('User'=>&$tmpUser,'username'=>&$this->username,'password'=>&$this->password));
        if (!static::$isMobile && $tmpUser->get('type') == 1) {
            $this->setMessage(static::$foglang['NotAllowedHere']);
            $this->redirect('index.php?node=logout');
        }
        $this->setCurUser($tmpUser);
        if (!$this->currentUser->isValid()) $this->setRedirMode();
    }
    public function mainLoginForm() {
        if (!$_SESSION['locale']) $this->setLang();
        if (in_array($_REQUEST['node'],array('login','logout'))) {
            $this->setMessage($_SESSION['FOG_MESSAGES']);
            $this->redirect('index.php');
        }
        echo '<form method="post" action="" id="login-form">';
        if (htmlentities($_REQUEST['node'],ENT_QUOTES,'utf-8') != 'logout') {
            foreach ($_REQUEST AS $key => &$value) {
                printf('<input type="hidden" name="%s" value="%s"/>',htmlentities($key,ENT_QUOTES,'utf-8'),htmlentities($value,ENT_QUOTES,'utf-8'));
                unset($value);
            }
        }
        $this->getLanguages();
        printf('<form method="post" action="" id="login-form"><label for="username">%s</label><input type="text" class="input" name="uname" id="username"/><label for="password">%s</label><input type="password" class="input" name="upass" id="password"/><label for="language">%s</label><select name="ulang" id="language">%s</select><label for="login-form-submit"> </label><input type="submit" value="%s" id="login-form-submit"/></form><div id="login-form-info"><p>%s: <b><i class="icon fa fa-circle-o-notch fa-spin fa-fw"></i></b></p><p>%s: <b><i class="icon fa fa-circle-o-notch fa-spin fa-fw"></i></b></p></div>',static::$foglang['Username'],static::$foglang['Password'],static::$foglang['LanguagePhrase'],$this->langMenu,static::$foglang['Login'],static::$foglang['FOGSites'],static::$foglang['LatestVer']);
    }
    public function mobileLoginForm() {
        if (!$_SESSION['locale']) $this->setLang();
        if (in_array($_REQUEST['node'],array('login','logout'))) $this->redirect('index.php');
        $this->getLanguages();
        printf('<div class="c"><p>%s</p><form method="post" action=""><br/><br/><label for="username">%s: </label><input type="text" name="uname" id="username"/><br/><br/><label for="password">%s: </label><input type="password" name="upass" id="password"/><br/><br/><label for="language">%s: </label><select name="ulang" id="language">%s</select><br/><br/><label for="login-form-submit"> </label><input type="submit" value="%s" id="login-form-submit"/></form></div>',static::$foglang['FOGMobile'],static::$foglang['Username'],static::$foglang['Password'],static::$foglang['LanguagePhrase'],$this->langMenu,static::$foglang['Login']);
    }
}
