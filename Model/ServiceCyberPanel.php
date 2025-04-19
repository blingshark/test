<?php
namespace Box\Mod\Hosting\CyberPanel\Model;

use FOSSBilling\Environment;

class ServiceCyberPanel extends \FOSSBilling\Service
{
    protected $table = 'service_cyberpanel';
    protected $id_column = 'id';

    public function getDomains($service_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE service_id = :service_id";
        return $this->di['db']->getAll($sql, [':service_id' => $service_id]);
    }

    public function getMainDomain($service_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE service_id = :service_id AND is_main = 1";
        return $this->di['db']->getRow($sql, [':service_id' => $service_id]);
    }

    public function addDomain($data)
    {
        $required = [
            'service_id' => 'Service ID is required',
            'domain' => 'Domain is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $isMain = isset($data['is_main']) ? (int)$data['is_main'] : 0;
        
        if ($isMain) {
            // If this is a main domain, make sure no other main domain exists
            $sql = "UPDATE service_cyberpanel_domain SET is_main = 0 WHERE service_id = :service_id";
            $this->di['db']->exec($sql, [':service_id' => $data['service_id']]);
        }
        
        $sql = "
            INSERT INTO service_cyberpanel_domain (
                service_id, 
                domain, 
                is_main, 
                created_at, 
                updated_at
            ) VALUES (
                :service_id, 
                :domain, 
                :is_main, 
                :created_at, 
                :updated_at
            )
        ";
        
        $params = [
            ':service_id' => $data['service_id'],
            ':domain' => $data['domain'],
            ':is_main' => $isMain,
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->di['db']->exec($sql, $params);
        
        return $this->di['db']->lastInsertId();
    }

    public function deleteDomain($domain_id)
    {
        $domain = $this->getDomain($domain_id);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        if ($domain['is_main']) {
            throw new \FOSSBilling\Exception('Cannot delete main domain');
        }
        
        $sql = "DELETE FROM service_cyberpanel_domain WHERE id = :id";
        $this->di['db']->exec($sql, [':id' => $domain_id]);
        
        return true;
    }

    public function getDomain($domain_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE id = :id";
        return $this->di['db']->getRow($sql, [':id' => $domain_id]);
    }

    public function getDomainByName($service_id, $domain)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE service_id = :service_id AND domain = :domain";
        return $this->di['db']->getRow($sql, [':service_id' => $service_id, ':domain' => $domain]);
    }

    public function getSubdomains($domain_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_subdomain WHERE domain_id = :domain_id";
        return $this->di['db']->getAll($sql, [':domain_id' => $domain_id]);
    }

    public function addSubdomain($data)
    {
        $required = [
            'domain_id' => 'Domain ID is required',
            'subdomain' => 'Subdomain name is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $domain = $this->getDomain($data['domain_id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        $sql = "
            INSERT INTO service_cyberpanel_subdomain (
                domain_id, 
                subdomain, 
                created_at, 
                updated_at
            ) VALUES (
                :domain_id, 
                :subdomain, 
                :created_at, 
                :updated_at
            )
        ";
        
        $params = [
            ':domain_id' => $data['domain_id'],
            ':subdomain' => $data['subdomain'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->di['db']->exec($sql, $params);
        
        return $this->di['db']->lastInsertId();
    }

    public function deleteSubdomain($subdomain_id)
    {
        $sql = "DELETE FROM service_cyberpanel_subdomain WHERE id = :id";
        $this->di['db']->exec($sql, [':id' => $subdomain_id]);
        
        return true;
    }

    public function getSubdomain($subdomain_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_subdomain WHERE id = :id";
        return $this->di['db']->getRow($sql, [':id' => $subdomain_id]);
    }

    public function getEmailAccounts($domain_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_email WHERE domain_id = :domain_id";
        return $this->di['db']->getAll($sql, [':domain_id' => $domain_id]);
    }

    public function addEmailAccount($data)
    {
        $required = [
            'domain_id' => 'Domain ID is required',
            'email' => 'Email address is required',
            'password' => 'Password is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $domain = $this->getDomain($data['domain_id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        $sql = "
            INSERT INTO service_cyberpanel_email (
                domain_id, 
                email, 
                password, 
                created_at, 
                updated_at
            ) VALUES (
                :domain_id, 
                :email, 
                :password, 
                :created_at, 
                :updated_at
            )
        ";
        
        $params = [
            ':domain_id' => $data['domain_id'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ];
        
        $this->di['db']->exec($sql, $params);
        
        return $this->di['db']->lastInsertId();
    }

    public function deleteEmailAccount($email_id)
    {
        $sql = "DELETE FROM service_cyberpanel_email WHERE id = :id";
        $this->di['db']->exec($sql, [':id' => $email_id]);
        
        return true;
    }

    public function getEmailAccount($email_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_email WHERE id = :id";
        return $this->di['db']->getRow($sql, [':id' => $email_id]);
    }

    public function getServiceConfig($service_id)
    {
        $sql = "SELECT * FROM service_cyberpanel WHERE id = :id";
        return $this->di['db']->getRow($sql, [':id' => $service_id]);
    }

    /**
     * Create SQL tables for the module
     */
    public static function install()
    {
        $db = Environment::get()->db;
        
        // Main service table
        $sql = "
            CREATE TABLE IF NOT EXISTS `service_cyberpanel` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `client_id` bigint(20) DEFAULT NULL,
                `package` varchar(255) DEFAULT NULL,
                `username` varchar(255) DEFAULT NULL,
                `password` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `client_id_idx` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $db->exec($sql);
        
        // Domains table
        $sql = "
            CREATE TABLE IF NOT EXISTS `service_cyberpanel_domain` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `service_id` bigint(20) DEFAULT NULL,
                `domain` varchar(255) DEFAULT NULL,
                `is_main` tinyint(1) DEFAULT '0',
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `service_id_idx` (`service_id`),
                UNIQUE KEY `service_domain_idx` (`service_id`, `domain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $db->exec($sql);
        
        // Subdomains table
        $sql = "
            CREATE TABLE IF NOT EXISTS `service_cyberpanel_subdomain` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `domain_id` bigint(20) DEFAULT NULL,
                `subdomain` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `domain_id_idx` (`domain_id`),
                UNIQUE KEY `domain_subdomain_idx` (`domain_id`, `subdomain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $db->exec($sql);
        
        // Email accounts table
        $sql = "
            CREATE TABLE IF NOT EXISTS `service_cyberpanel_email` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `domain_id` bigint(20) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `password` varchar(255) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `domain_id_idx` (`domain_id`),
                UNIQUE KEY `domain_email_idx` (`domain_id`, `email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $db->exec($sql);
        
        return true;
    }

    /**
     * Remove SQL tables when uninstalling the module
     */
    public static function uninstall()
    {
        $db = Environment::get()->db;
        
        $tables = [
            'service_cyberpanel',
            'service_cyberpanel_domain',
            'service_cyberpanel_subdomain',
            'service_cyberpanel_email',
        ];
        
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS `$table`;";
            $db->exec($sql);
        }
        
        return true;
    }
} 
