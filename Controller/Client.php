<?php
namespace Box\Mod\Hosting\CyberPanel\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
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
    
    public function indexAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domains = $domainModel->getDomains($id);
        
        return $this->di['twig']->render('mod_cyberpanel_client_index.html.twig', [
            'service_id' => $id,
            'service' => $service,
            'domains' => $domains,
        ]);
    }
    
    public function domainsAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domains = $domainModel->getDomains($id);
        
        return $this->di['twig']->render('mod_cyberpanel_client_domains.html.twig', [
            'service_id' => $id,
            'service' => $service,
            'domains' => $domains,
        ]);
    }
    
    public function domainAction($id, $domain_id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domain = $domainModel->getDomain($domain_id);
        
        if (!$domain) {
            throw new \FOSSBilling\Exception('Domain not found');
        }
        
        return $this->di['twig']->render('mod_cyberpanel_client_domain.html.twig', [
            'service_id' => $id,
            'service' => $service,
            'domain' => $domain,
        ]);
    }
    
    public function subdomainsAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domains = $domainModel->getDomains($id);
        
        $allSubdomains = [];
        foreach ($domains as $domain) {
            $subdomains = $domainModel->getSubdomains($domain['id']);
            foreach ($subdomains as $subdomain) {
                $subdomain['domain_name'] = $domain['domain'];
                $subdomain['full_domain'] = $subdomain['subdomain'] . '.' . $domain['domain'];
                $allSubdomains[] = $subdomain;
            }
        }
        
        return $this->di['twig']->render('mod_cyberpanel_client_subdomains.html.twig', [
            'service_id' => $id,
            'service' => $service,
            'domains' => $domains,
            'subdomains' => $allSubdomains,
        ]);
    }
    
    public function emailsAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        $domainModel = new \Box\Mod\Hosting\CyberPanel\Model\ServiceCyberPanel();
        $domainModel->setDi($this->di);
        
        $domains = $domainModel->getDomains($id);
        
        $allEmails = [];
        foreach ($domains as $domain) {
            $emails = $domainModel->getEmailAccounts($domain['id']);
            foreach ($emails as $email) {
                $email['domain_name'] = $domain['domain'];
                $email['full_email'] = $email['email'] . '@' . $domain['domain'];
                $allEmails[] = $email;
            }
        }
        
        return $this->di['twig']->render('mod_cyberpanel_client_emails.html.twig', [
            'service_id' => $id,
            'service' => $service,
            'domains' => $domains,
            'emails' => $allEmails,
        ]);
    }
    
    public function manageAction($id)
    {
        $service = $this->di['db']->getExistingModelById('ServiceCyberPanel', $id, 'Service not found');
        
        $client = $this->di['session']->get('client_id');
        if ($service->client_id != $client) {
            throw new \FOSSBilling\Exception('Access denied');
        }
        
        return $this->di['twig']->render('mod_cyberpanel_client_manage.html.twig', [
            'service_id' => $id,
            'service' => $service,
        ]);
    }
}