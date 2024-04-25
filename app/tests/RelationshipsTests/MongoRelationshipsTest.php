<?php

use Tests\Models\Client;
use Tests\Models\ClientLog;
use Tests\Models\ClientProfile;
use Tests\Factories\ClientFactory;
use Tests\Factories\ClientLogFactory;
use Tests\Factories\ClientProfileFactory;
use Tests\Factories\CompanyFactory;
use Tests\Models\Company;
use PDPhilip\Elasticsearch\Schema\Schema;


beforeEach(function () {
    $this->companies = 3;
    $this->clientsPerCompany = 2;
    $this->logsPerClient = 10;
    
    $this->totalCompanies = $this->companies;
    $this->totalClients = $this->totalCompanies * $this->clientsPerCompany;
    $this->totalClientProfiles = $this->totalClients;
    $this->totalClientLogs = $this->totalClients * $this->logsPerClient;
    
});

$mongoInstalled = class_exists('MongoDB\Laravel\Eloquent\Model');


it('should truncate all our models to start fresh', function () {
    //init a client to create DB & collection
    
    Company::deleteIndexIfExists();
    Client::truncate();
    ClientLog::deleteIndexIfExists();
    ClientProfile::deleteIndexIfExists();
    
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertTrue(Client::count() === 0);
    $this->assertFalse(Schema::hasIndex('client_logs'));
    $this->assertFalse(Schema::hasIndex('client_profiles'));
    
})->skip(!$mongoInstalled, 'Skipping - MongoDB not installed');

it('should build the MongoDB and ES data', function () {
    
    $i = 0;
    while ($i < $this->companies) {
        $cf = new CompanyFactory;
        $company = $cf->makeOne();
        $company->saveWithoutRefresh();
        $companyId = $company->_id;
        $j = 0;
        while ($j < $this->clientsPerCompany) {
            $clientF = new ClientFactory;
            $client = $clientF->makeOne();
            $client->company_id = $companyId;
            $client->save();
            $clientId = $client->_id;
            
            $clientProfileF = new ClientProfileFactory;
            $clientProfile = $clientProfileF->makeOne();
            $clientProfile->client_id = $clientId;
            $clientProfile->save();
            
            $k = 0;
            while ($k < $this->logsPerClient) {
                $clf = new ClientLogFactory;
                $clientLog = $clf->makeOne();
                $clientLog->client_id = $clientId;
                $clientLog->saveWithoutRefresh();
                $k++;
            }
            $j++;
        }
        
        $i++;
    }
    //Sleep to let ES catch up
    sleep(2);
    
    $this->assertTrue(Company::count() === $this->totalCompanies);
    $this->assertTrue(Client::count() === $this->totalClients);
    $this->assertTrue(ClientProfile::count() === $this->totalClientProfiles);
    $this->assertTrue(ClientLog::count() === $this->totalClientLogs);
})->skip(!$mongoInstalled, 'Skipping - MongoDB not installed');


it('should show client (mongo) relationships to ES models', function () {
    $client = Client::first();
    
    $this->assertTrue(!empty($client->clientLogs) && count($client->clientLogs) === $this->logsPerClient);
    $this->assertTrue(!empty($client->clientProfile->_id));
    $this->assertTrue(!empty($client->company->_id));
})->skip(!$mongoInstalled, 'Skipping - MongoDB not installed');

it('should show ES relationships to Mongo', function () {
    $clientProfile = ClientProfile::first();
    $clientLog = ClientLog::first();
    $company = Company::first();
    
    $this->assertTrue(!empty($clientProfile->client->_id));
    $this->assertTrue(!empty($clientLog->client->_id));
    $this->assertTrue(!empty($company->clients) && count($company->clients) === $this->clientsPerCompany);
})->skip(!$mongoInstalled, 'Skipping - MongoDB not installed');


it('should clean up everything', function () {
    Company::deleteIndexIfExists();
    Client::truncate();
    ClientLog::deleteIndexIfExists();
    ClientProfile::deleteIndexIfExists();
    
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertTrue(Client::count() === 0);
    $this->assertFalse(Schema::hasIndex('client_logs'));
    $this->assertFalse(Schema::hasIndex('client_profiles'));
})->skip(!$mongoInstalled, 'Skipping - MongoDB not installed');