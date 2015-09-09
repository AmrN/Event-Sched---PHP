<?php
 
/**
 * Handling database connection
 */
class DbConnect {
 
    private $conn;
 
    function __construct() {       
    }
 
    /**
     * Establishing database connection
     * @return database connection handler
     */
    
    function connect() {
        require_once dirname(__FILE__) . '/config.php';
        try {
            $con_string = sprintf("mysql:host=%s;dbname=%s",
                    DB_HOST, DB_NAME);

            $this->conn = new PDO($con_string, DB_USERNAME, DB_PASSWORD);
            $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        } catch(PDOException $e) {
            echo "Error Connecting to DB: " . $e->getMessage();
        }
        
        return $this->conn;
    }
    
 
}
 
?>