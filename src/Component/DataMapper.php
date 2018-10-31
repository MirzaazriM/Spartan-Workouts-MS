<?php
namespace Component;

use Model\Core\Helper\Monolog\MonologSender;
use Model\Core\Helper\SQL\Helper;
use PDO;
/**
 * Description of DataMapper
 *
 * @author Arslan Hajdarevic <arslan.h@tech387.com>
 */
class DataMapper {
    
    protected $connection;
    protected $configuration;
    protected $sqlHelper;
    protected $monologHelper;
    
    /**
     * Creates new mapper instance
     *
     * @param PDO $connection
     * @param array $configuration A list of table name aliases
     *
     * @codeCoverageIgnore
     */
    public function __construct(PDO $connection, array $configuration)
    {
        // , array $configuration
        $this->connection = $connection;
        $this->sqlHelper = new Helper();
        $this->configuration = $configuration;
        $this->monologHelper = new MonologSender();
    }
    
}
