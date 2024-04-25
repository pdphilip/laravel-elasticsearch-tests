<?php

use App\Models\User;
use App\Models\UserLog;
use App\Models\Company;
use Database\Factories\UserFactory;
use Database\Factories\UserLogFactory;
use Database\Factories\CompanyFactory;
use PDPhilip\Elasticsearch\Schema\Schema;

$skip = false;
beforeEach(function () {
    $this->companies = 3;
    $this->usersPerCompany = 10;
    $this->logsPerUser = 40;
    
    $this->totalCompanies = $this->companies;
    $this->totalUsers = $this->totalCompanies * $this->usersPerCompany;
    $this->totalUserLogs = $this->totalUsers * $this->logsPerUser;
    
});
$fieldChecks = [
    'log_status_9'                       => 0,
    'log_status_9_by_code'               => [],
    'log_status_9_by_user_id'            => [],
    'log_status_9_by_user_id_code'       => [],
    'log_status_9_by_user_id_code_total' => [],
    'log_status_9_count_user_id'         => 0,
    'log_status_9_min_user_id'           => 999,
    'log_status_9_max_user_id'           => 0,
    'log_status_9_sum_user_id'           => 0,
    'log_status_9_avg_user_id'           => 0,

];


it('should remove the existing index if it exists and truncate users model', function () {
    User::truncate();
    Company::deleteIndexIfExists();
    UserLog::deleteIndexIfExists();
    $this->assertDatabaseCount('users', 0);
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('user_logs'));
    
})->skip($skip);

it('should create the models and relationships for testing', function () use (&$fieldChecks) {
    $i = 0;
    while ($i < $this->companies) {
        $cf = new CompanyFactory;
        $company = $cf->makeOne();
        $company->saveWithoutRefresh();
        $companyId = $company->_id;
        
        $k = 0;
        while ($k < $this->usersPerCompany) {
            $uf = new UserFactory;
            $user = $uf->makeOne();
            $user->company_id = $companyId;
            $user->save();
            $userId = $user->id;
            
            $m = 0;
            while ($m < $this->logsPerUser) {
                $ulf = new UserLogFactory;
                $userLog = $ulf->makeOne();
                $userLog->user_id = $userId;
                $userLog->company_id = $companyId;
                $userLog->saveWithoutRefresh();
                $m++;
                if ($userLog->status == 9) {
                    
                    if (!isset($fieldChecks['log_status_9_by_code'][$userLog->code])) {
                        $fieldChecks['log_status_9_by_code'][$userLog->code] = 0;
                    }
                    if (!isset($fieldChecks['log_status_9_by_user_id'][$userId])) {
                        $fieldChecks['log_status_9_by_user_id'][$userId] = 0;
                    }
                    if (!isset($fieldChecks['log_status_9_by_user_id_code'][$userId][$userLog->code])) {
                        $fieldChecks['log_status_9_by_user_id_code'][$userId][$userLog->code] = 0;
                    }
                    if (!isset($fieldChecks['log_status_9_by_user_id_code_total'][$userId.'_'.$userLog->code])) {
                        $fieldChecks['log_status_9_by_user_id_code_total'][$userId.'_'.$userLog->code] = 0;
                    }
                    if ($userId < $fieldChecks['log_status_9_min_user_id']) {
                        $fieldChecks['log_status_9_min_user_id'] = $userId;
                    }
                    if ($userId > $fieldChecks['log_status_9_max_user_id']) {
                        $fieldChecks['log_status_9_max_user_id'] = $userId;
                    }
                    $fieldChecks['log_status_9']++;
                    $fieldChecks['log_status_9_by_code'][$userLog->code]++;
                    $fieldChecks['log_status_9_by_user_id'][$userId]++;
                    $fieldChecks['log_status_9_by_user_id_code'][$userId][$userLog->code]++;
                    $fieldChecks['log_status_9_by_user_id_code_total'][$userId.'_'.$userLog->code]++;
                }
                
                
            }
            $k++;
        }
        $i++;
    }
    
    $sum = 0;
    $count = 0;
    $avg = 0;
    foreach ($fieldChecks['log_status_9_by_user_id'] as $userId => $vals) {
        $count++;
        $sum += $userId;
    }
    if ($count > 0) {
        $avg = $sum / $count;
    }
    $fieldChecks['log_status_9_count_user_id'] = $count;
    $fieldChecks['log_status_9_sum_user_id'] = $sum;
    $fieldChecks['log_status_9_avg_user_id'] = $avg;
    
    
    //Sleep to let ES catch up
    sleep(2);
    
    $this->assertTrue(User::count() === $this->totalUsers);
    $this->assertTrue(Company::count() === $this->totalCompanies);
    $this->assertTrue(UserLog::count() === $this->totalUserLogs);
});


it('should find user logs with status 9 and return distinct user_ids', function () use (&$fieldChecks) {
    $logs = UserLog::where('status', 9)->distinct()->get('user_id');
    $this->assertTrue(count($logs) === count($fieldChecks['log_status_9_by_user_id']));
    
});

it('should find user logs with status 9 and return distinct user_ids and maintain model relationships with userLogs -> User -> Company', function () {
    $logs = UserLog::where('status', 9)->distinct()->get('user_id');
    $found = false;
    foreach ($logs as $log) {
        $found = !empty($log->user->company->_id);
        $this->assertTrue($found);
    }
    $this->assertTrue($found);
});

it('should find user logs with status 9 and return distinct user_ids ordered my user_id asc', function () {
    $logs = UserLog::where('status', 9)->distinct()->orderBy('user_id')->get('user_id');
    $lastId = 0;
    foreach ($logs as $log) {
        $currentId = $log->user_id;
        $this->assertTrue($currentId > $lastId);
        $lastId = $currentId;
    }
});

it('should find user logs with status 9 and return distinct user_ids and their counts', function () use (&$fieldChecks) {
    $logs = UserLog::where('status', 9)->distinct(true)->orderBy('user_id')->get('user_id');
    
    foreach ($logs as $log) {
        $currentId = $log->user_id;
        $this->assertTrue($fieldChecks['log_status_9_by_user_id'][$currentId] == $log->user_id_count);
    }
});

it('should find user logs with status 9 and return distinct user_ids and their counts ordered by count desc', function () {
    $logs = UserLog::where('status', 9)->distinct(true)->orderByDesc('_count')->get('user_id');
    $lastCount = 100000;
    foreach ($logs as $log) {
        $currentCount = $log->user_id_count;
        $this->assertTrue($currentCount > 0);
        $this->assertTrue($currentCount <= $lastCount);
        $lastCount = $currentCount;
    }
});


it('should find user logs with status 9 and return distinct code values', function () use (&$fieldChecks) {
    $logs = UserLog::where('status', 9)->distinct()->get('code');
    $this->assertTrue(count($logs) === count($fieldChecks['log_status_9_by_code']));
});


it('should find user logs with status 9 and return both distinct code and user_id values', function () use (&$fieldChecks) {
    $logs = UserLog::where('status', 9)->distinct()->get(['user_id', 'code']);
    $this->assertTrue(count($logs) > 0);
    $this->assertTrue(count($logs) === count($fieldChecks['log_status_9_by_user_id_code_total']));
    foreach ($logs as $log) {
        $this->assertTrue(!empty($log->user_id));
        $this->assertTrue(!empty($log->code));
        $this->assertTrue(!empty(!empty($fieldChecks['log_status_9_by_user_id_code'][$log->user_id][$log->code])));
        
    }
});

it('should find user logs with status 9 and return both distinct code and user_id values with their counts', function () use (&$fieldChecks) {
    $logs = UserLog::where('status', 9)->distinct(true)->get(['user_id', 'code']);
    $this->assertTrue(count($logs) > 0);
    $this->assertTrue(count($logs) === count($fieldChecks['log_status_9_by_user_id_code_total']));
    foreach ($logs as $log) {
        $this->assertTrue(!empty($log->user_id));
        $this->assertTrue(!empty($log->user_id_count));
        $this->assertTrue(!empty($log->code));
        $this->assertTrue(!empty($log->code_count));
        $this->assertTrue(!empty(!empty($fieldChecks['log_status_9_by_user_id_code'][$log->user_id][$log->code])));
        $this->assertTrue($log->code_count == $fieldChecks['log_status_9_by_user_id_code'][$log->user_id][$log->code], 'Code count is '.$log->code_count.' and should be '.$fieldChecks['log_status_9_by_user_id_code'][$log->user_id][$log->code]);
        
    }
});

it('should be that distinct and groupBy are synonymous', function () {
    $distincts = UserLog::where('status', 9)->orderByDesc('user_id')->distinct()->get(['user_id', 'code']);
    $groupBys = UserLog::where('status', 9)->orderByDesc('user_id')->groupBy(['user_id', 'code'])->get();
    
    $this->assertTrue(count($distincts) === count($groupBys));
    
    foreach ($distincts as $i => $distinct) {
        $this->assertTrue($distinct->toArray() === $groupBys[$i]->toArray());
    }
    
});

it('should be able to paginate with groupBy and distinct', function () {
    $distinctPagi = UserLog::where('status', 9)->orderByDesc('user_id')->select(['user_id', 'code'])->distinct()->paginate(5);
    $groupBysPagi = UserLog::where('status', 9)->orderByDesc('user_id')->groupBy(['user_id', 'code'])->paginate(5);
    $this->assertTrue($distinctPagi->total() > 0);
    $this->assertTrue($distinctPagi->toArray() === $groupBysPagi->toArray());
});

it('should perform aggregate count on distinct and groupBy queries', function () use (&$fieldChecks) {
    $distinctCountWithSelect = UserLog::where('status', 9)->distinct()->select(['user_id'])->count();
    $distinctCount = UserLog::where('status', 9)->distinct()->count('user_id');
    $groupByCount = UserLog::where('status', 9)->groupBy('user_id')->count();
    
    $this->assertTrue($distinctCountWithSelect === $distinctCount);
    $this->assertTrue($distinctCount === $groupByCount);
    $this->assertTrue($groupByCount === $fieldChecks['log_status_9_count_user_id']);
});

it('should perform aggregate sum on distinct and groupBy queries', function () use (&$fieldChecks) {
    $dist = UserLog::where('status', 9)->distinct()->sum('user_id');
    $grp = UserLog::where('status', 9)->groupBy(['user_id'])->sum('user_id');
    
    $this->assertTrue($dist === $grp);
    $this->assertTrue($grp === $fieldChecks['log_status_9_sum_user_id']);
});

it('should perform aggregate max on distinct and groupBy queries', function () use (&$fieldChecks) {
    $dist = UserLog::where('status', 9)->distinct()->max('user_id');
    $grp = UserLog::where('status', 9)->groupBy(['user_id'])->max('user_id');
    
    $this->assertTrue($dist === $grp);
    $this->assertTrue($grp === $fieldChecks['log_status_9_max_user_id']);
});

it('should perform aggregate min on distinct and groupBy queries', function () use (&$fieldChecks) {
    $dist = UserLog::where('status', 9)->distinct()->min('user_id');
    $grp = UserLog::where('status', 9)->groupBy(['user_id'])->min('user_id');
    
    $this->assertTrue($dist === $grp);
    $this->assertTrue($grp === $fieldChecks['log_status_9_min_user_id']);
});

it('should perform aggregate avg on distinct and groupBy queries', function () use (&$fieldChecks) {
    $dist = UserLog::where('status', 9)->distinct()->avg('user_id');
    $grp = UserLog::where('status', 9)->groupBy(['user_id'])->avg('user_id');
    
    $this->assertTrue($dist === $grp);
    $this->assertTrue($grp === $fieldChecks['log_status_9_avg_user_id']);
});


it('should clean up everything', function () {
    User::truncate();
    Company::deleteIndexIfExists();
    UserLog::deleteIndexIfExists();
    
    $this->assertDatabaseCount('users', 0);
    $this->assertFalse(Schema::hasIndex('companies'));
    $this->assertFalse(Schema::hasIndex('user_logs'));
    
})->skip($skip);