<?php
namespace Box\Mod\Hosting\CyberPanel\Api;

class Admin extends \Api_Abstract
{
    /**
     * Get module configuration
     */
    public function get_config($data)
    {
        $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        return $service->getModuleConfig();
    }
    
/**
     * Update module configuration
     */
    public function update_config($data)
    {
        $required = [
            'cyberpanel_url' => 'CyberPanel URL is required',
            'api_key' => 'API key is required',
            'admin_username' => 'Admin username is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        foreach ($data as $key => $value) {
            $this->di['db']->exec('INSERT INTO extension_meta (extension, param, value) VALUES (:ext, :key, :value) ON DUPLICATE KEY UPDATE value = :value', [
                ':ext' => 'mod_hosting_cyberpanel',
                ':key' => $key,
                ':value' => $value,
            ]);
        }
        
        $this->di['logger']->info('Updated CyberPanel module configuration');
        
        return true;
    }
    
    /**
     * Test connection to CyberPanel API
     */
    public function test_connection($data)
    {
        try {
            $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
            $api = $service->getApi();
            
            // Try to get websites list to test connection
            $result = $api->getWebsitesList();
            
            return ['result' => true, 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['result' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get service details
     */
    public function get_service($data)
    {
        $required = [
            'id' => 'Service ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        return [
            'id' => $service->id,
            'client_id' => $service->client_id,
            'package' => $service->package,
            'username' => $service->username,
            'password' => $service->password,
            'created_at' => $service->created_at,
            'updated_at' => $service->updated_at,
        ];
    }
    
    /**
     * Get domains for a service
     */
    public function get_domains($data)
    {
        $required = [
            'id' => 'Service ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        return $domainModel->getDomains($data['id']);
    }
    
    /**
     * Add a domain to a service
     */
    public function add_domain($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain' => 'Domain is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        // Check if this domain already exists
        $existingDomain = $domainModel->getDomainByName($data['id'], $data['domain']);
        if ($existingDomain) {
            throw new \FOSSBilling\Exception('Domain already exists for this service');
        }
        
        // Add domain to database
        $isMain = isset($data['is_main']) ? (bool)$data['is_main'] : false;
        $domain_id = $domainModel->addDomain([
            'service_id' => $data['id'],
            'domain' => $data['domain'],
            'is_main' => $isMain ? 1 : 0,
        ]);
        
        // Create website in CyberPanel
        try {
            $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
            $api = $service->getApi();
            
            $api->createWebsite(
                $data['domain'],
                $service->package
            );
            
            // Install SSL
            $api->installSSL($data['domain']);
        } catch (\Exception $e) {
            // Rollback - delete domain from database
            $domainModel->deleteDomain($domain_id);
            
            throw new \FOSSBilling\Exception('Failed to create website in CyberPanel: ' . $e->getMessage());
        }
        
        return ['id' => $domain_id];
    }
    
    /**
     * Delete a domain
     */
    public function delete_domain($data)
    {
        $required = [
            'id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domain = $domainModel->getDomain($data['id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        // Delete website in CyberPanel
        try {
            $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
            $api = $service->getApi();
            
            $api->deleteWebsite($domain['domain']);
        } catch (\Exception $e) {
            // Continue anyway, just log the error
            error_log('CyberPanel API Error: ' . $e->getMessage());
        }
        
        // Delete domain from database
        $domainModel->deleteDomain($data['id']);
        
        return true;
    }
    
    /**
     * Reset a website
     */
    public function reset_website($data)
    {
        $required = [
            'service_id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $service->resetAccount($data['service_id'], $data['domain_id']);
        
        return true;
    }
}