<?php
namespace Box\Mod\Hosting\CyberPanel\Api;

class Client extends \Api_Abstract
{
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
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        return [
            'id' => $service->id,
            'package' => $service->package,
            'username' => $service->username,
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
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        return $domainModel->getDomains($data['id']);
    }
    
    /**
     * Get a specific domain
     */
    public function get_domain($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domain = $domainModel->getDomain($data['domain_id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        return $domain;
    }
    
    /**
     * Get subdomains for a domain
     */
    public function get_subdomains($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domain = $domainModel->getDomain($data['domain_id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        return $domainModel->getSubdomains($data['domain_id']);
    }
    
    /**
     * Create a subdomain
     */
    public function create_subdomain($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
            'subdomain' => 'Subdomain name is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $subdomain_id = $serviceObj->createSubdomain($data['id'], $data['domain_id'], $data['subdomain']);
        
        return ['id' => $subdomain_id];
    }
    
    /**
     * Delete a subdomain
     */
    public function delete_subdomain($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
            'subdomain_id' => 'Subdomain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $serviceObj->deleteSubdomain($data['id'], $data['domain_id'], $data['subdomain_id']);
        
        return true;
    }
    
    /**
     * Get email accounts for a domain
     */
    public function get_email_accounts($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domain = $domainModel->getDomain($data['domain_id']);
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        return $domainModel->getEmailAccounts($data['domain_id']);
    }
    
    /**
     * Create an email account
     */
    public function create_email_account($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
            'email' => 'Email address is required',
            'password' => 'Password is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $email_id = $serviceObj->createEmailAccount($data['id'], $data['domain_id'], $data['email'], $data['password']);
        
        return ['id' => $email_id];
    }
    
    /**
     * Delete an email account
     */
    public function delete_email_account($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
            'email_id' => 'Email ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $serviceObj->deleteEmailAccount($data['id'], $data['domain_id'], $data['email_id']);
        
        return true;
    }
    
    /**
     * Install an application on a domain
     */
    public function install_application($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
            'app' => 'Application name is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $serviceObj->installApplication($data['id'], $data['domain_id'], $data['app'], $data);
        
        return true;
    }
    
    /**
     * Reset a website
     */
    public function reset_website($data)
    {
        $required = [
            'id' => 'Service ID is required',
            'domain_id' => 'Domain ID is required',
        ];
        
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Check if client owns this service
        $client_id = $this->getIdentity()->id;
        if ($service->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $serviceObj = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $serviceObj->resetAccount($data['id'], $data['domain_id']);
        
        return true;
    }
}