<?php

class ModelExtensionRetailcrmCustomer extends Model {

    public function uploadToCrm($customers) 
    {
        $this->initApi();

        if(empty($customers))
            return false;

        $customersToCrm = array();

        foreach($customers as $customer) {
            $customersToCrm[] = $this->process($customer);
        }

        $chunkedCustomers = array_chunk($customersToCrm, 50);

        foreach($chunkedCustomers as $customersPart) {
            $this->retailcrmApi->customersUpload($customersPart);
        }
    }

    public function changeInCrm($customer) 
    {
        $this->initApi();

        if(empty($customer))
            return false;
        
        $customerToCrm = $this->process($customer);
        
        $this->retailcrmApi->customersEdit($customerToCrm);
    }

    private function process($customer) {
        $customerToCrm = array(
            'externalId' => $customer['customer_id'],
            'firstName' => $customer['firstname'],
            'lastName' => $customer['lastname'],
            'email' => $customer['email'],
            'phones' => array(
                array(
                    'number' => $customer['telephone']
                )
            ),
            'createdAt' => $customer['date_added']
        );

        if (isset($customer['address'])) {
            $customerToCrm['address'] = array(
                'index' => $customer['address']['postcode'],
                'countryIso' => $customer['address']['iso_code_2'],
                'region' => $customer['address']['zone'],
                'city' => $customer['address']['city'],
                'text' => $customer['address']['address_1'] . ' ' . $customer['address']['address_2'] 
            );
        }

        return $customerToCrm;
    }

    protected function initApi()
    {   
        $moduleTitle = $this->getModuleTitle();
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting($moduleTitle);

        if(empty($settings[$moduleTitle . '_url']) || empty($settings[$moduleTitle . '_apikey']))
            return false;

        require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

        $this->retailcrmApi = new RetailcrmProxy(
            $settings[$moduleTitle . '_url'],
            $settings[$moduleTitle . '_apikey'],
            DIR_SYSTEM . 'storage/logs/retailcrm.log',
            $settings[$moduleTitle . '_apiversion']
        );
    }

    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }
}
