<?php
namespace Box\Mod\Hosting\CyberPanel\Service;

use Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel;
use Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanelDomain;

class Service
{
    protected $di;
    
    public function __construct($di)
    {
        $this->di = $di;
    }
    
    /**
     * Get module configuration
     */
    public function getModuleConfig()
    {
        $config = $this->di['db']->getAssoc('SELECT param, value FROM extension_meta WHERE extension = "mod_hosting_cyberpanel"');
        
        return [
            'cyberpanel_url' => isset($config['cyberpanel_url']) ? $config['cyberpanel_url'] : null,
            'api_key' => isset($config['api_key']) ? $config['api_key'] : null,
            'verify_ssl' => isset($config['verify_ssl']) ? (bool)$config['verify_ssl'] : true,
            'default_package' => isset($config['default_package']) ? $config['default_package'] : 'Default',
            'admin_username' => isset($config['admin_username']) ? $config['admin_username'] : null,
        ];
    }
    
    /**
     * Get CyberPanel API client
     */
    public function getApi()
    {
        $config = $this->getModuleConfig();
        return new CyberPanelApi($this->di, $config);
    }
    
    /**
     * Create a new hosting account
     */
    public function createAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelCreate', 'params' => $data]);
        
        $client = $this->di['db']->getExistingModelById('Client', $data['client_id'], 'Client not found');
        $clientEmail = $client->email;
        
        $model = new ServiceCyberPanel();
        $model->setDi($this->di);
        
        $model->client_id = $data['client_id'];
        $model->package = $data['package'] ?? $this->getModuleConfig()['default_package'];
        $model->username = !empty($data['username']) ? $data['username'] : $this->generateUsername($data['domain']);
        $model->password = !empty($data['password']) ? $data['password'] : $this->di['tools']->generatePassword();
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        
        $serviceId = $this->di['db']->store($model);
        
        // Add main domain
        if (!empty($data['domain'])) {
            $domainModel = new ServiceCyberPanelDomain($this->di);
            $domainModel->findOrCreateDomain($serviceId, $data['domain'], true);
            
            // Create website in CyberPanel
            try {
                $api = $this->getApi();
                $api->createWebsite(
                    $data['domain'],
                    $model->package,
                    $clientEmail
                );
                
                // Install SSL
                $api->installSSL($data['domain']);
            } catch (\Exception $e) {
                // Log error but don't stop the process
                error_log('CyberPanel API Error: ' . $e->getMessage());
            }
        }
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelCreate', 'params' => ['id' => $serviceId]]);
        
        return $serviceId;
    }
    
    /**
     * Activate a hosting account
     */
    public function activateAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelActivate', 'params' => $data]);
        
        $model = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Get domains
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domains = $domainModel->getDomains($data['id']);
        
        // Activate main domain in CyberPanel if it exists
        foreach ($domains as $domain) {
            if ($domain['is_main']) {
                try {
                    $api = $this->getApi();
                    $api->createWebsite(
                        $domain['domain'],
                        $model->package
                    );
                    
                    // Install SSL
                    $api->installSSL($domain['domain']);
                } catch (\Exception $e) {
                    // Log error but don't stop the process
                    error_log('CyberPanel API Error: ' . $e->getMessage());
                }
                break;
            }
        }
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelActivate', 'params' => ['id' => $data['id']]]);
        
        return true;
    }
    
    /**
     * Suspend a hosting account
     */
    public function suspendAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelSuspend', 'params' => $data]);
        
        // Currently CyberPanel API doesn't support suspending websites, but we can mark it in our database
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelSuspend', 'params' => ['id' => $data['id']]]);
        
        return true;
    }
    
    /**
     * Unsuspend a hosting account
     */
    public function unsuspendAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelUnsuspend', 'params' => $data]);
        
        // Currently CyberPanel API doesn't support unsuspending websites, but we can mark it in our database
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelUnsuspend', 'params' => ['id' => $data['id']]]);
        
        return true;
    }
    
    /**
     * Cancel a hosting account
     */
    public function cancelAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelCancel', 'params' => $data]);
        
        $model = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Get domains
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domains = $domainModel->getDomains($data['id']);
        
        // Delete websites in CyberPanel
        $api = $this->getApi();
        foreach ($domains as $domain) {
            try {
                $api->deleteWebsite($domain['domain']);
            } catch (\Exception $e) {
                // Log error but don't stop the process
                error_log('CyberPanel API Error: ' . $e->getMessage());
            }
        }
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelCancel', 'params' => ['id' => $data['id']]]);
        
        return true;
    }
    
    /**
     * Delete a hosting account
     */
    public function deleteAccount($data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeServiceCyberPanelDelete', 'params' => $data]);
        
        $model = $this->di['db']->getExistingModelById('ServiceCyberPanel', $data['id'], 'Service not found');
        
        // Get domains
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domains = $domainModel->getDomains($data['id']);
        
        // Delete websites in CyberPanel
        $api = $this->getApi();
        foreach ($domains as $domain) {
            try {
                $api->deleteWebsite($domain['domain']);
            } catch (\Exception $e) {
                // Log error but don't stop the process
                error_log('CyberPanel API Error: ' . $e->getMessage());
            }
        }
        
        // Delete from database
        $this->di['db']->trash($model);
        
        $this->di['events_manager']->fire(['event' => 'onAfterServiceCyberPanelDelete', 'params' => ['id' => $data['id']]]);
        
        return true;
    }
    
    /**
     * Reset a hosting account (delete and recreate the website)
     */
    public function resetAccount($service_id, $domain_id)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        try {
            $api = $this->getApi();
            $api->resetWebsite($domain['domain'], $serviceModel->package);
            
            // Install SSL
            $api->installSSL($domain['domain']);
        } catch (\Exception $e) {
            throw new \FOSSBilling\Exception('Failed to reset website: ' . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Install an application on a domain
     */
    public function installApplication($service_id, $domain_id, $app, $data)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        $api = $this->getApi();
        
        switch ($app) {
            case 'wordpress':
                $required = ['email', 'title', 'username', 'password'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        throw new \FOSSBilling\Exception('Field ' . $field . ' is required');
                    }
                }
                
                $path = isset($data['path']) ? $data['path'] : '/';
                
                $result = $api->installWordPress(
                    $domain['domain'],
                    $data['email'],
                    $data['title'],
                    $data['username'],
                    $data['password'],
                    $path
                );
                break;
                
            case 'nextcloud':
                $required = ['email', 'username', 'password'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        throw new \FOSSBilling\Exception('Field ' . $field . ' is required');
                    }
                }
                
                $path = isset($data['path']) ? $data['path'] : '/';
                
                $result = $api->installNextCloud(
                    $domain['domain'],
                    $data['email'],
                    $data['username'],
                    $data['password'],
                    $path
                );
                break;
                
            default:
                throw new \FOSSBilling\Exception('Application not supported');
        }
        
        return true;
    }
    
    /**
     * Create a subdomain
     */
    public function createSubdomain($service_id, $domain_id, $subdomain)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        // Create subdomain in database
        $subdomainId = $domainModel->addSubdomain([
            'domain_id' => $domain_id,
            'subdomain' => $subdomain,
        ]);
        
        // Create subdomain in CyberPanel
        try {
            $api = $this->getApi();
            $api->createSubdomain($subdomain, $domain['domain']);
            
            // Install SSL for the subdomain
            $api->installSSL($subdomain . '.' . $domain['domain']);
        } catch (\Exception $e) {
            // Rollback - delete subdomain from database
            $domainModel->deleteSubdomain($subdomainId);
            
            throw new \FOSSBilling\Exception('Failed to create subdomain: ' . $e->getMessage());
        }
        
        return $subdomainId;
    }
    
    /**
     * Delete a subdomain
     */
    public function deleteSubdomain($service_id, $domain_id, $subdomain_id)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        $subdomain = $domainModel->getSubdomain($subdomain_id);
        
        if (!$subdomain) {
            throw new \FOSSBilling\Exception('Subdomain not found');
        }
        
        // Delete subdomain in CyberPanel
        try {
            $api = $this->getApi();
            $api->deleteSubdomain($subdomain['subdomain'], $domain['domain']);
        } catch (\Exception $e) {
            throw new \FOSSBilling\Exception('Failed to delete subdomain: ' . $e->getMessage());
        }
        
        // Delete subdomain from database
        $domainModel->deleteSubdomain($subdomain_id);
        
        return true;
    }
    
    /**
     * Create an email account
     */
    public function createEmailAccount($service_id, $domain_id, $email, $password)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        // Create email account in CyberPanel
        try {
            $api = $this->getApi();
            $api->createEmailAccount($domain['domain'], $email, $password);
        } catch (\Exception $e) {
            throw new \FOSSBilling\Exception('Failed to create email account: ' . $e->getMessage());
        }
        
        // Create email account in database
        $emailId = $domainModel->addEmailAccount([
            'domain_id' => $domain_id,
            'email' => $email,
            'password' => $password,
        ]);
        
        return $emailId;
    }
    
    /**
     * Delete an email account
     */
    public function deleteEmailAccount($service_id, $domain_id, $email_id)
    {
        $serviceModel = $this->di['db']->getExistingModelById('ServiceCyberPanel', $service_id, 'Service not found');
        
        $domainModel = new ServiceCyberPanel();
        $domainModel->setDi($this->di);
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        $email = $domainModel->getEmailAccount($email_id);
        
        if (!$email) {
            throw new \FOSSBilling\Exception('Email account not found');
        }
        
        // Delete email account in CyberPanel
        try {
            $api = $this->getApi();
            $api->deleteEmailAccount($domain['domain'], $email['email']);
        } catch (\Exception $e) {
            throw new \FOSSBilling\Exception('Failed to delete email account: ' . $e->getMessage());
        }
        
        // Delete email account from database
        $domainModel->deleteEmailAccount($email_id);
        
        return true;
    }
    
    /**
     * Generate a username based on domain name
     */
    private function generateUsername($domain)
    {
        // Remove non-alphanumeric characters and truncate
        $username = preg_replace('/[^a-z0-9]/i', '', $domain);
        $username = substr($username, 0, 8);
        
        // Add random suffix to avoid duplicates
        $username .= rand(100, 999);
        
        return strtolower($username);
    }
}