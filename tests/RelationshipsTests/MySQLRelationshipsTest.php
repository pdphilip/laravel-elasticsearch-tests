<?php

use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Tests\Factories\CompanyFactory;
use Tests\Factories\CompanyProfileFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\UserLogFactory;
use Tests\Factories\UserProfileFactory;
use Tests\Migrations\MysqlMigration;
use Tests\Models\Avatar;
use Tests\Models\Company;
use Tests\Models\CompanyProfile;
use Tests\Models\Photo;
use Tests\Models\User;
use Tests\Models\UserLog;
use Tests\Models\UserProfile;

$skip = false;

beforeEach(function () {
    $this->companies = 1;
    $this->usersPerCompany = 3;
    $this->logsPerUser = 10;
    $this->photosPerUser = 5;
    $this->photosPerCompany = 2;
    
    
    $this->totalCompanies = $this->companies;
    $this->totalUsers = $this->totalCompanies * $this->usersPerCompany;
    $this->totalUserLogs = $this->totalUsers * $this->logsPerUser;
    $this->totalUserProfiles = $this->totalUsers;
    $this->totalCompanyProfiles = $this->totalCompanies;
    $this->totalPhotos = $this->totalUsers * $this->photosPerUser + $this->totalCompanies * $this->photosPerCompany;
    $this->totalAvatars = $this->totalUsers + $this->totalCompanies;
    
});

it('should migrate mysql/sqlite tables', function () {
    $migration = new MysqlMigration();
    $migration->up();
    $this->assertTrue($migration->hasTables());
});

it('should truncate all our models to start fresh', function () {
    
    
    User::truncate();
    Company::deleteIndexIfExists();
    UserLog::deleteIndexIfExists();
    UserProfile::deleteIndexIfExists();
    CompanyProfile::deleteIndexIfExists();
    Avatar::deleteIndexIfExists();
    Photo::truncate();
    
    $this->assertDatabaseCount('users', 0);
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('user_logs'));
    $this->assertFalse(Schema::hasIndex('user_profiles'));
    $this->assertFalse(Schema::hasIndex('company_profiles'));
    $this->assertFalse(Schema::hasIndex('avatars'));
    $this->assertDatabaseCount('photos', 0);
    
});

it('should create a user_logs schema with agent.geo and title fields', function () {
    
    Schema::create('user_logs', function (IndexBlueprint $index) {
        $index->keyword('title');
        $index->geo('agent.geo');
    });
    
    $this->assertTrue(Schema::hasIndex('user_logs'), 'user_logs index does not exist');
    $this->assertTrue(Schema::hasField('user_logs', 'agent.geo'), 'agent.geo field does not exist');
    $this->assertTrue(Schema::hasFields('user_logs', ['agent.geo', 'title']), 'agent.geo and title fields do not exist');
});

it('should build the mySQL user and ES data', function () {
    $i = 0;
    while ($i < $this->companies) {
        $cf = new CompanyFactory;
        $company = $cf->makeOne();
        $company->saveWithoutRefresh();
        $companyId = $company->_id;
        
        $avatar = new Avatar;
        $avatar->url = $company->name.'_pic.jpg';
        $avatar->imageable_id = $companyId;
        $avatar->imageable_type = 'Tests\Models\Company';
        $avatar->save();
        
        $j = 0;
        while ($j < $this->photosPerCompany) {
            $photo = new Photo;
            $photo->url = $company->name.'-photo-'.$j.'.jpg';
            $photo->photoable_id = $companyId;
            $photo->photoable_type = 'Tests\Models\Company';
            $photo->save();
            $j++;
        }
        
        $cpf = new CompanyProfileFactory;
        $companyProfile = $cpf->makeOne();
        $companyProfile->company_id = $companyId;
        $companyProfile->saveWithoutRefresh();
        $k = 0;
        while ($k < $this->usersPerCompany) {
            $uf = new UserFactory;
            $user = $uf->makeOne();
            $user->company_id = $companyId;
            $user->save();
            $userId = $user->id;
            $upf = new UserProfileFactory;
            $userProfile = $upf->makeOne();
            $userProfile->user_id = $userId;
            $userProfile->saveWithoutRefresh();
            
            $avatar = new Avatar;
            $avatar->url = $user->first_name.'_avatar.jpg';
            $avatar->imageable_id = $userId;
            $avatar->imageable_type = 'Tests\Models\User';
            $avatar->save();
            
            $l = 0;
            while ($l < $this->photosPerUser) {
                $photo = new Photo;
                $photo->url = $user->first_name.'-photo-'.$l.'.jpg';
                $photo->photoable_id = $userId;
                $photo->photoable_type = 'Tests\Models\User';
                $photo->save();
                $l++;
            }
            
            $m = 0;
            while ($m < $this->logsPerUser) {
                $ulf = new UserLogFactory;
                $userLog = $ulf->makeOne();
                $userLog->user_id = $userId;
                $userLog->company_id = $companyId;
                $userLog->saveWithoutRefresh();
                $m++;
            }
            $k++;
        }
        $i++;
    }
    //Sleep to let ES catch up
    sleep(2);
    
    $this->assertTrue(User::count() === $this->totalUsers);
    $this->assertTrue(Company::count() === $this->totalCompanies);
    $this->assertTrue(UserLog::count() === $this->totalUserLogs);
    $this->assertTrue(UserProfile::count() === $this->totalUserProfiles);
    $this->assertTrue(CompanyProfile::count() === $this->totalCompanyProfiles);
    $this->assertTrue(Photo::count() === $this->totalPhotos);
    $this->assertTrue(Avatar::count() === $this->totalAvatars);
});


it('should show user relationships to ES models', function () {
    $user = User::first();
    $this->assertTrue(!empty($user->userLogs) && count($user->userLogs) === $this->logsPerUser);
    $this->assertTrue(!empty($user->userProfile->_id));
    $this->assertTrue(!empty($user->avatar->_id));
    $this->assertTrue(!empty($user->photos) && count($user->photos) === $this->photosPerUser);
    $this->assertTrue(!empty($user->company->_id));
    $this->assertTrue(!empty($user->company->userLogs) && count($user->company->userLogs) === ($this->logsPerUser * $this->usersPerCompany));
    $this->assertTrue(!empty($user->company->companyProfile->_id));
    $this->assertTrue(!empty($user->company->avatar->_id));
    $this->assertTrue(count($user->company->photos) === $this->photosPerCompany);
    
});

it('should show user log (ES) relationship to user', function () {
    $userLog = UserLog::first();
    $this->assertTrue(!empty($userLog->user->id));
    $this->assertTrue(!empty($userLog->user->company->_id));
    $this->assertTrue(!empty($userLog->user->userProfile->_id));
    $this->assertTrue(!empty($userLog->company->_id));
    $this->assertTrue(!empty($userLog->company->users) && count($userLog->company->users) === $this->usersPerCompany);
    $this->assertTrue(!empty($userLog->company->companyProfile->_id));
    
});

it('should show 1 to 1 ES relationships for user and company', function () {
    $companyProfile = CompanyProfile::first();
    $userProfile = UserProfile::first();
    
    $this->assertTrue(!empty($companyProfile->company->_id));
    $this->assertTrue(!empty($userProfile->user->id));
});


it('should clean up everything', function () {
    User::truncate();
    Company::deleteIndexIfExists();
    UserLog::deleteIndexIfExists();
    UserProfile::deleteIndexIfExists();
    CompanyProfile::deleteIndexIfExists();
    Avatar::deleteIndexIfExists();
    Photo::truncate();
    
    $this->assertDatabaseCount('users', 0);
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('user_logs'));
    $this->assertFalse(Schema::hasIndex('user_profiles'));
    $this->assertFalse(Schema::hasIndex('company_profiles'));
    $this->assertFalse(Schema::hasIndex('avatars'));
    $this->assertDatabaseCount('photos', 0);
    
    $migration = new MysqlMigration();
    $migration->down();
    $this->assertFalse($migration->hasTables());
    
});