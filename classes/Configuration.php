<?php


namespace Influx;

class Configuration
{


    function __construct()
    {
        $this->db = new mysqli(MYSQL_HOST, MYSQL_LOGIN, MYSQL_MDP, MYSQL_BDD);
        $this->set_charset('utf8mb4');
        $this->query('SET NAMES utf8mb4');
    }

    public function getAll()
    {
        $config = '';

        $query_configuration = 'select * from leed_configuration';
        $result_configuration = $this->db->query($query_configuration);

        while ($row = $result_configuration->fetch_array()) {
            $config[$row['key']] = $row['value'];
        }

        return $config;
    }

    public function get($key)
    {
        $query_configuration = "select value from leed_configuration where key = '" . $key . "'";
        $result_configuration = $this->db->query($query_configuration);

        while ($row = $result_configuration->fetch_array()) {
            $config = $row['value'];
        }

        return $config
    }

    public function put($key, $value)
    {
        $query_configuration = "update configuration set value = '" . $value . "' where key = '" . $key . "'";
        $this->db->query($query_configuration);
    }

    protected function createSynchronisationCode()
    {
        return substr(sha1(rand(0, 30) . time() . rand(0, 30)), 0, 10);
    }

    public function add($key, $value)
    {

        $query_configuration = "insert into configuration values('" . $key . "', '" . $value . "')";
        $this->db->query($query_configuration);
    }


    protected function generateSalt()
    {
        return '' . mt_rand() . mt_rand();
    }

    function getId()
    {
        return $this->id;
    }

    function getKey()
    {
        return $this->key;
    }

    function setKey($key)
    {
        $this->key = $key;
    }

    function getValue()
    {
        return $this->value;
    }

    function setValue($value)
    {
        $this->value = $value;
    }
}