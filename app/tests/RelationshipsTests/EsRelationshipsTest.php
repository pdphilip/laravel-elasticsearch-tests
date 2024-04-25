<?php

use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use tests\Factories\CompanyFactory;
use tests\Factories\CompanyLogFactory;
use tests\Factories\CompanyProfileFactory;
use tests\Models\Avatar;
use tests\Models\Company;
use tests\Models\CompanyLog;
use tests\Models\CompanyProfile;
use tests\Models\EsPhoto;

$skip = false;

beforeEach(function () {
    $this->companies = 1;
    $this->logsPerCompany = 10;
    $this->photosPerCompany = 5;
    
    
    $this->totalCompanies = $this->companies;
    $this->totalCompaniesLogs = $this->companies * $this->logsPerCompany;
    $this->totalCompanyProfiles = $this->totalCompanies;
    $this->totalPhotos = $this->totalCompanies * $this->photosPerCompany;
    $this->totalAvatars = $this->totalCompanies;
    
});


it('should truncate all our models to start fresh', function () {
    
    
    Company::deleteIndexIfExists();
    CompanyLog::deleteIndexIfExists();
    CompanyProfile::deleteIndexIfExists();
    Avatar::deleteIndexIfExists();
    EsPhoto::deleteIndexIfExists();
    
    
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('company_logs'));
    $this->assertFalse(Schema::hasIndex('company_profiles'));
    $this->assertFalse(Schema::hasIndex('avatars'));
    $this->assertFalse(Schema::hasIndex('es_photos'));
    
});

it('should create a company_logs schema with agent.geo and title fields', function () {
    
    Schema::create('company_logs', function (IndexBlueprint $index) {
        $index->keyword('title');
        $index->geo('agent.geo');
    });
    
    $this->assertTrue(Schema::hasIndex('company_logs'), 'user_logs index does not exist');
    $this->assertTrue(Schema::hasField('company_logs', 'agent.geo'), 'agent.geo field does not exist');
    $this->assertTrue(Schema::hasFields('company_logs', ['agent.geo', 'title']), 'agent.geo and title fields do not exist');
});

it('should build  ES data', function () {
    $i = 0;
    while ($i < $this->companies) {
        $cf = new CompanyFactory;
        $company = $cf->makeOne();
        $company->saveWithoutRefresh();
        $companyId = $company->_id;
        
        $avatar = new Avatar;
        $avatar->url = $company->name.'_pic.jpg';
        $avatar->imageable_id = $companyId;
        $avatar->imageable_type = 'tests\Models\Company';
        $avatar->saveWithoutRefresh();
        
        $j = 0;
        while ($j < $this->photosPerCompany) {
            $photo = new EsPhoto;
            $photo->url = $company->name.'-photo-'.$j.'.jpg';
            $photo->photoable_id = $companyId;
            $photo->photoable_type = 'tests\Models\Company';
            $photo->saveWithoutRefresh();
            $j++;
        }
        
        $cpf = new CompanyProfileFactory;
        $companyProfile = $cpf->makeOne();
        $companyProfile->company_id = $companyId;
        $companyProfile->saveWithoutRefresh();
        $k = 0;
        while ($k < $this->logsPerCompany) {
            $lf = new CompanyLogFactory;
            $user = $lf->makeOne();
            $user->company_id = $companyId;
            $user->saveWithoutRefresh();
            $k++;
        }
        $i++;
    }
    //Sleep to let ES catch up
    sleep(2);
    
    $this->assertTrue(Company::count() === $this->totalCompanies);
    $this->assertTrue(CompanyLog::count() === $this->totalCompaniesLogs);
    $this->assertTrue(CompanyProfile::count() === $this->totalCompanyProfiles);
    $this->assertTrue(EsPhoto::count() === $this->totalPhotos);
    $this->assertTrue(Avatar::count() === $this->totalAvatars);
});


it('should show company relationships', function () {
    $company = Company::first();
    $this->assertTrue(!empty($company->companyLogs) && count($company->companyLogs) === $this->logsPerCompany);
    $this->assertTrue(!empty($company->companyProfile->_id));
    $this->assertTrue(!empty($company->avatar->_id));
    $this->assertTrue(!empty($company->esPhotos) && count($company->esPhotos) === $this->photosPerCompany);
    
});

it('should show user log (ES) relationship to user', function () {
    $companyLog = CompanyLog::first();
    $this->assertTrue(!empty($companyLog->company->_id));
    $this->assertTrue(!empty($companyLog->company->companyProfile->_id));
    
    
});

it('should show 1 to 1 ES relationships for user and company', function () {
    $companyProfile = CompanyProfile::first();
    $this->assertTrue(!empty($companyProfile->company->_id));
});


it('should clean up everything', function () {
    Company::deleteIndexIfExists();
    CompanyLog::deleteIndexIfExists();
    CompanyProfile::deleteIndexIfExists();
    Avatar::deleteIndexIfExists();
    EsPhoto::deleteIndexIfExists();
    
    
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('company_logs'));
    $this->assertFalse(Schema::hasIndex('company_profiles'));
    $this->assertFalse(Schema::hasIndex('avatars'));
    $this->assertFalse(Schema::hasIndex('es_photos'));
    
});