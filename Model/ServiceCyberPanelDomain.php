<?php
namespace Box\Mod\Hosting\CyberPanel\Model;

class ServiceCyberPanelDomain
{
    private $di;
    
    public function __construct($di)
    {
        $this->di = $di;
    }
    
    public function getDomainConfig($domain)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE domain = :domain LIMIT 1";
        return $this->di['db']->getRow($sql, [':domain' => $domain]);
    }
    
    public function findOrCreateDomain($service_id, $domain, $isMain = false)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE service_id = :service_id AND domain = :domain LIMIT 1";
        $domainRow = $this->di['db']->getRow($sql, [
            ':service_id' => $service_id,
            ':domain' => $domain
        ]);
        
        if ($domainRow) {
            return $domainRow;
        }
        
        // Create domain
        $model = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $model->setDi($this->di);
        
        $id = $model->addDomain([
            'service_id' => $service_id,
            'domain' => $domain,
            'is_main' => $isMain ? 1 : 0
        ]);
        
        return $this->getDomainById($id);
    }
    
    public function getDomainById($id)
    {
        $sql = "SELECT * FROM service_cyberpanel_domain WHERE id = :id LIMIT 1";
        return $this->di['db']->getRow($sql, [':id' => $id]);
    }
    
    public function getSubdomains($domain_id)
    {
        $sql = "SELECT * FROM service_cyberpanel_subdomain WHERE domain_id = :domain_id";
        return $this->di['db']->getAll($sql, [':domain_id' => $domain_id]);
    }
    
    public function findOrCreateSubdomain($domain_id, $subdomain)
    {
        $sql = "SELECT * FROM service_cyberpanel_subdomain WHERE domain_id = :domain_id AND subdomain = :subdomain LIMIT 1";
        $subdomainRow = $this->di['db']->getRow($sql, [
            ':domain_id' => $domain_id,
            ':subdomain' => $subdomain
        ]);
        
        if ($subdomainRow) {
            return $subdomainRow;
        }
        
        // Create subdomain
        $model = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $model->setDi($this->di);
        
        $id = $model->addSubdomain([
            'domain_id' => $domain_id,
            'subdomain' => $subdomain
        ]);
        
        return $this->getSubdomainById($id);
    }
    
    public function getSubdomainById($id)
    {
        $sql = "SELECT * FROM service_cyberpanel_subdomain WHERE id = :id LIMIT 1";
        return $this->di['db']->getRow($sql, [':id' => $id]);
    }
    
    public function getFullDomain($domain, $subdomain = null)
    {
        if (empty($subdomain)) {
            return $domain;
        }
        
        return $subdomain . '.' . $domain;
    }
}