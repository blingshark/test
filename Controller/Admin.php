<?php
namespace Box\Mod\Hosting\CyberPanel\Controller;

class Admin implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;
    
    public function setDi($di)
    {
        $this->di = $di;
    }
    
    public function getDi()
    {
        return $this->di;
    }
    
    public function fetchNavigation()
    {
        return [
            'group' => [
                'index' => 101,
                'location' => 'hosting',
                'label' => 'Hosting',
                'uri' => $this->di['url']->adminLink('hosting'),
                'class' => 'hosting',
                'sprite_class' => 'dark-sprite-icon sprite-server',
            ],
            'subpages' => [
                [
                    'location' => 'cyberpanel',
                    'index' => 150,
                    'label' => 'CyberPanel',
                    'uri' => $this->di['url']->adminLink('hosting/cyberpanel'),
                    'class' => '',
                ],
            ],
        ];
    }
    
    public function register(\Box\Mod\Extension\Manager $manager)
    {
        $manager->register([
            'id' => 'mod_hosting_cyberpanel',
            'type' => 'mod',
            'name' => 'CyberPanel',
            'description' => 'CyberPanel hosting management module for FOSSBilling',
            'setting' => [
                'cyberpanel_url' => [
                    'text',
                    [
                        'label' => 'CyberPanel URL',
                        'description' => 'Full URL to your CyberPanel installation (e.g., https://cyberpanel.example.com)',
                        'required' => true,
                    ],
                ],
                'api_key' => [
                    'password',
                    [
                        'label' => 'API Key',
                        'description' => 'CyberPanel API key for authentication',
                        'required' => true,
                    ],
                ],
                'verify_ssl' => [
                    'checkbox',
                    [
                        'label' => 'Verify SSL',
                        'description' => 'Enable or disable SSL verification for API requests',
                        'default' => true,
                    ],
                ],
                'default_package' => [
                    'text',
                    [
                        'label' => 'Default Package',
                        'description' => 'Default package name to use when creating websites',
                        'required' => true,
                        'default' => 'Default',
                    ],
                ],
                'admin_username' => [
                    'text',
                    [
                        'label' => 'Admin Username',
                        'description' => 'Username of the CyberPanel admin account',
                        'required' => true,
                    ],
                ],
            ],
        ]);
    }
    
    public function install()
    {
        // Install database tables
        $model = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        return $model->install();
    }
    
    public function uninstall()
    {
        // Uninstall database tables
        $model = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        return $model->uninstall();
    }
    
    public function indexAction()
    {
        return $this->di['twig']->render('mod_cyberpanel_admin_index.html.twig');
    }
    
    public function configAction()
    {
        $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
        $config = $service->getModuleConfig();
        
        return $this->di['twig']->render('mod_cyberpanel_admin_config.html.twig', ['config' => $config]);
    }
    
    public function manageAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domains = $domainModel->getDomains($id);
        
        return $this->di['twig']->render('mod_cyberpanel_admin_manage.html.twig', [
            'service' => $service,
            'domains' => $domains,
        ]);
    }
    
    public function saveConfigAction()
    {
        $data = $this->di['request']->post();
        
        foreach ($data as $key => $value) {
            $this->di['db']->exec('INSERT INTO extension_meta (extension, param, value) VALUES (:ext, :key, :value) ON DUPLICATE KEY UPDATE value = :value', [
                ':ext' => 'mod_hosting_cyberpanel',
                ':key' => $key,
                ':value' => $value,
            ]);
        }
        
        $this->di['logger']->info('Updated CyberPanel module configuration');
        
        return $this->di['response_json']->setMessage('Configuration saved');
    }
    
    public function testConnectionAction()
    {
        try {
            $service = new \Box\Mod\Hosting\CyberPanel\Service\Service($this->di);
            $api = $service->getApi();
            
            // Try to get websites list to test connection
            $result = $api->getWebsitesList();
            
            return $this->di['response_json']->setMessage('Connection successful');
        } catch (\Exception $e) {
            return $this->di['response_json']->setError($e->getMessage());
        }
    }
}