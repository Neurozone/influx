<?php


namespace Influx;

class Configuration {
    protected $id,$key,$value,$confTab;

    function __construct(){
        parent::__construct();
    }
    public function getAll(){
        if(!isset($_SESSION['configuration'])){
            $configurationManager = new Configuration();
            $configs = $configurationManager->populate();
            $confTab = array();
            foreach($configs as $config){
                $this->confTab[$config->getKey()] = $config->getValue();
            }
            $_SESSION['configuration'] = serialize($this->confTab);
        }else{
            $this->confTab = unserialize($_SESSION['configuration']);
        }
    }
    public function get($key){
        return (isset($this->confTab[$key])?$this->confTab[$key]:'');
    }
    public function put($key,$value){
        $configurationManager = new Configuration();
        if (isset($this->confTab[$key])){
            $configurationManager->change(array('value'=>$value),array('key'=>$key));
        } else {
            $configurationManager->add($key,$value);
        }
        $this->confTab[$key] = $value;
        unset($_SESSION['configuration']);
    }
    protected function createSynchronisationCode() {
        return substr(sha1(rand(0,30).time().rand(0,30)),0,10);
    }
    public function add($key,$value){

        $this->setKey($key);
        $this->setValue($value);
        $this->save();
        $this->confTab[$key] = $value;
        unset($_SESSION['configuration']);
    }
    public function setDefaults() {
        foreach($this->options as $option => $defaultValue) {
            switch($option) {
                case 'language':
                    $value = isset($_POST['install_changeLngLeed']) ? $_POST['install_changeLngLeed'] : $defaultValue;
                    break;
                case 'theme':
                    $value = isset($_POST['template']) ? $_POST['template'] : $defaultValue;
                    break;
                case 'synchronisationCode':
                    $value = $this->createSynchronisationCode();
                    break;
                case 'root':
                    $root = $_POST['root'];
                    $value = (substr($root, strlen($root)-1)=='/'?$root:$root.'/');
                    break;
                case 'cryptographicSalt':
                    $value = $this->generateSalt();
                    break;
                default:
                    $value = $defaultValue;
                    break;
            }
            $this->add($option, $value);
        }
    }
    protected function generateSalt() {
        return ''.mt_rand().mt_rand();
    }
    function getId(){
        return $this->id;
    }
    function getKey(){
        return $this->key;
    }
    function setKey($key){
        $this->key = $key;
    }
    function getValue(){
        return $this->value;
    }
    function setValue($value){
        $this->value = $value;
    }
}