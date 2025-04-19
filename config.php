<?php
/**
 * CyberPanel module configuration
 */
return [
    'id' => 'cyberpanel',
    'name' => 'CyberPanel',
    'description' => 'CyberPanel hosting management module for FOSSBilling',
    'icon_url' => 'icon.png',
    'version' => '1.0.0',
    
    'author' => 'FOSSBilling Community',
    'author_url' => 'https://fossbilling.org/',
    
    'form' => [
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
]; 
