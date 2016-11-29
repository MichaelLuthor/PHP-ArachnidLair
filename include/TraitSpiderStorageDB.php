<?php
trait TraitSpiderStorageDB {
    /** @var medoo */
    private $db = null;
    
    /** @param array $config */
    public function setDBConfig( $config ) {
        $this->db = new medoo($config);
    }
    
    /**
     * @param string $table
     * @param string $data
     */
    public function insert( $table, $data ) {
        $this->db->insert($table, $data);
    }
    
    /** @return integer */
    public function max( $table, $colum ) {
        return $this->db->max($table, $colum);
    }
}