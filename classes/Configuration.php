<?php

namespace Influx;

class Configuration
{

    private $name;
    private $value;
    private $db;

    function __construct($db)
    {
        $this->db = $db;
        $this->db->set_charset('utf8mb4');
        $this->db->query('SET NAMES utf8mb4');
    }

    public function getAll()
    {
        $config = array();

        $query_configuration = 'select * from configuration';
        $result_configuration = $this->db->query($query_configuration);

        while ($row = $result_configuration->fetch_array()) {

            $name = $row['name'];
            $config[$name] = $row['value'];

        }

        return $config;
    }

    public function get($name)
    {
        $query_configuration = "select value from configuration where name = '" . $name . "'";
        $result_configuration = $this->db->query($query_configuration);

        while ($row = $result_configuration->fetch_array()) {
            $config = $row['value'];
        }

        return $config;
    }

    public function put($name, $value)
    {
        $query_configuration = "update configuration set value = '" . $value . "' where name = '" . $name . "'";
        $this->db->query($query_configuration);
    }

    public function add($name, $value)
    {

        $query_configuration = "insert into configuration values('" . $name . "', '" . $value . "')";
        $this->db->query($query_configuration);
    }

    function getId()
    {
        return $this->id;
    }

    function getName()
    {
        return $this->key;
    }

    function setName($name)
    {
        $this->name = $name;
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