 
<?php
namespace Box\Mod\Hosting\CyberPanel\Service;

class CyberPanelApi
{
    private $url;
    private $apiKey;
    private $adminUsername;
    private $verifySSL;
    private $di;
    
    public function __construct($di, $config = [])
    {
        $this->di = $di;
        
        $this->url = isset($config['cyberpanel_url']) ? rtrim($config['cyberpanel_url'], '/') : null;
        $this->apiKey = isset($config['api_key']) ? $config['api_key'] : null;
        $this->adminUsername = isset($config['admin_username']) ? $config['admin_username'] : null;
        $this->verifySSL = isset($config['verify_ssl']) ? (bool)$config['verify_ssl'] : true;
        
        if (empty($this->url) || empty($this->apiKey) || empty($this->adminUsername)) {
            throw new \FOSSBilling\Exception('CyberPanel API configuration is incomplete');
        }
    }
    
    /**
     * Send a request to CyberPanel API
     */
    public function sendRequest($endpoint, $data = [], $method = 'POST')
    {
        $url = $this->url . '/api/' . ltrim($endpoint, '/');
        
        // Add API key to all requests
        $data['apiKey'] = $this->apiKey;
        
        $curl = curl_init();
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
            $options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/x-www-form-urlencoded',
            ];
        } else {
            $url .= '?' . http_build_query($data);
            $options[CURLOPT_URL] = $url;
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            throw new \FOSSBilling\Exception('CyberPanel API Error: ' . $err);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \FOSSBilling\Exception('Invalid JSON response from CyberPanel API: ' . $response);
        }
        
        if (isset($result['error_message'])) {
            throw new \FOSSBilling\Exception('CyberPanel API Error: ' . $result['error_message']);
        }
        
        return $result;
    }
    
    /**
     * Create a website in CyberPanel
     */
    public function createWebsite($domain, $package, $email = null)
    {
        $data = [
            'adminUser' => $this->adminUsername,
            'domainName' => $domain,
            'packageName' => $package,
            'websiteOwner' => substr(str_replace(['.', '-'], '', $domain), 0, 8) . rand(100, 999),
            'ownerEmail' => $email ?: 'admin@' . $domain,
        ];
        
        return $this->sendRequest('createWebsite', $data);
    }
    
    /**
     * Delete a website in CyberPanel
     */
    public function deleteWebsite($domain)
    {
        $data = [
            'domainName' => $domain,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('deleteWebsite', $data);
    }
    
    /**
     * Install SSL for a domain
     */
    public function installSSL($domain)
    {
        $data = [
            'domainName' => $domain,
            'adminUser' => $this->adminUsername,
            'sslCheck' => 1,
        ];
        
        return $this->sendRequest('issueSSL', $data);
    }
    
    /**
     * Create a subdomain in CyberPanel
     */
    public function createSubdomain($subdomain, $domain)
    {
        $data = [
            'masterDomain' => $domain,
            'domainName' => $subdomain,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('createChildDomain', $data);
    }
    
    /**
     * Delete a subdomain in CyberPanel
     */
    public function deleteSubdomain($subdomain, $domain)
    {
        $data = [
            'childDomain' => $subdomain . '.' . $domain,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('deleteChildDomain', $data);
    }
    
    /**
     * Create an email account in CyberPanel
     */
    public function createEmailAccount($domain, $username, $password)
    {
        $data = [
            'domain' => $domain,
            'email' => $username,
            'password' => $password,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('submitEmailCreation', $data);
    }
    
    /**
     * Delete an email account in CyberPanel
     */
    public function deleteEmailAccount($domain, $email)
    {
        $data = [
            'domain' => $domain,
            'email' => $email,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('submitEmailDeletion', $data);
    }
    
    /**
     * List email accounts for a domain
     */
    public function listEmailAccounts($domain)
    {
        $data = [
            'domain' => $domain,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('getEmailAccounts', $data);
    }
    
    /**
     * Install WordPress on a domain
     */
    public function installWordPress($domain, $email, $title, $username, $password, $path = '/')
    {
        $data = [
            'domainName' => $domain,
            'email' => $email,
            'title' => $title,
            'username' => $username,
            'password' => $password,
            'path' => $path,
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('installWordPress', $data);
    }
    
    /**
     * Install NextCloud on a domain
     */
    public function installNextCloud($domain, $email, $username, $password, $path = '/')
    {
        $data = [
            'domainName' => $domain,
            'path' => $path,
            'adminUser' => $this->adminUsername,
            'email' => $email,
            'username' => $username,
            'password' => $password,
        ];
        
        return $this->sendRequest('installNextCloud', $data);
    }
    
    /**
     * Reset a domain (delete and recreate)
     */
    public function resetWebsite($domain, $package, $email = null)
    {
        try {
            $this->deleteWebsite($domain);
        } catch (\Exception $e) {
            // Ignore deletion errors, may not exist
        }
        
        // Create new website
        return $this->createWebsite($domain, $package, $email);
    }
    
    /**
     * Get website list
     */
    public function getWebsitesList()
    {
        $data = [
            'adminUser' => $this->adminUsername,
        ];
        
        return $this->sendRequest('websitesListsForUser', $data);
    }
    
    /**
     * Check if the CyberPanel API is configured and accessible
     */