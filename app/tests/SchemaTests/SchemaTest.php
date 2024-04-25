<?php


use PDPhilip\Elasticsearch\Schema\IndexBlueprint;
use PDPhilip\Elasticsearch\Schema\AnalyzerBlueprint;
use PDPhilip\Elasticsearch\Schema\Schema;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->prefix = DB::connection('elasticsearch')->getConfig('index_prefix');
});


it('should clear any existing indices', function () {
    Schema::deleteIfExists('contacts');
    Schema::deleteIfExists('products');
    Schema::deleteIfExists('holding_products');
    $this->assertFalse(Schema::hasIndex('contacts'));
    $this->assertFalse(Schema::hasIndex('products'));
    $this->assertFalse(Schema::hasIndex('holding_products'));
});

it('should be that there no existing indices', function () {
    $indexes = Schema::getIndices();
    $this->assertTrue(count($indexes) === 0);
});

it('should create an index', function () {
    $contacts = Schema::create('contacts', function (IndexBlueprint $index) {
        //first_name & last_name is automatically added to this field,
        //you can search by full_name without ever writing to full_name
        $index->text('first_name')->copyTo('full_name');
        $index->text('last_name')->copyTo('full_name');
        $index->text('full_name');
        
        //Multiple types => Order matters ::
        //Top level `email` will be a searchable text field
        //Sub Property will be a keyword type which can be sorted using orderBy('email.keyword')
        $index->text('email');
        $index->keyword('email');
        
        //Dates have an optional formatting as second parameter
        $index->date('first_contact', 'epoch_second');
        $index->ip('user_ip');
        //Objects are defined with dot notation:
        $index->text('products.name');
        $index->float('products.price')->coerce(false);
        
        //Disk space considerations ::
        //Not indexed and not searchable:
        $index->keyword('internal_notes')->docValues(false);
        //Remove scoring for search:
        $index->array('tags')->norms(false);
        //Remove from index, can't search by this field but can still use for aggregations:
        $index->integer('score')->index(false);
        
        //If null is passed as value, then it will be saved as 'NA' which is searchable
        $index->keyword('favorite_color')->nullValue('NA');
        
        $index->nested('meta', [
                'properties' => [
                    'model'    => [
                        'type' => 'keyword',
                    ],
                    'question' => [
                        'type' => 'keyword',
                    ],
                    'answer'   => [
                        'type' => 'text',
                    ],
                ],
            ]
        );
        
        //Alias Example
        $index->text('notes');
        $index->alias('comments', 'notes');
        
        $index->geo('last_login');
        $index->date('created_at');
        $index->date('updated_at');
        
        //Settings
        $index->settings('number_of_shards', 3);
        $index->settings('number_of_replicas', 2);
        
        //Other Mappings
        $index->map('dynamic', false);
        $index->map('date_detection', false);
        
        //Custom Mapping
        $index->mapProperty('purchase_history', 'flattened');
    });
    
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['mappings']));
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['settings']));
    $this->assertTrue($contacts[$this->prefix.'_contacts']['mappings']['properties']['meta']['properties']['model']['type'] == 'keyword');
});

it('should set an analyser', function () {
    $contacts = Schema::setAnalyser('contacts', function (AnalyzerBlueprint $settings) {
        $settings->analyzer('my_custom_analyzer')
            ->type('custom')
            ->tokenizer('punctuation')
            ->filter(['lowercase', 'english_stop'])
            ->charFilter(['emoticons']);
        $settings->tokenizer('punctuation')
            ->type('pattern')
            ->pattern('[ .,!?]');
        $settings->charFilter('emoticons')
            ->type('mapping')
            ->mappings([":) => _happy_", ":( => _sad_"]);
        $settings->filter('english_stop')
            ->type('stop')
            ->stopwords('_english_');
    });
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['settings']['index']['analysis']['analyzer']['my_custom_analyzer']));
});

it('should return mappings', function () {
    $contacts = Schema::getMappings('contacts');
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['mappings']));
});

it('should return settings', function () {
    $contacts = Schema::getSettings('contacts');
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['settings']));
});

it('should not be able to create an index that already exists', function () {
    try {
        Schema::create('contacts', function (IndexBlueprint $index) {
            $index->text('x_name');
            $index->mapProperty('purchase_history_x', 'flattened');
        });
        $this->assertTrue(false);
    } catch (Exception $e) {
        $this->assertTrue(true);
    }
});

it('should be able to modify an index', function () {
    $contacts = Schema::modify('contacts', function (IndexBlueprint $index) {
        $index->text('my_favorite_color');
    });
    $this->assertTrue(!empty($contacts[$this->prefix.'_contacts']['mappings']['properties']['my_favorite_color']));
});

it('should find the index and certain fields', function () {
    $hasIndex = Schema::hasIndex('contacts');
    $this->assertTrue($hasIndex);
    $hasIndex = Schema::hasIndex('contactz');
    $this->assertFalse($hasIndex);
    $hasField = Schema::hasField('contacts', 'my_favorite_color');
    $this->assertTrue($hasField);
    $hasField = Schema::hasField('contacts', 'my_favorite_colorzzz');
    $this->assertFalse($hasField);
    $hasFields = Schema::hasFields('contacts', ['my_favorite_color', 'full_name', 'internal_notes']);
    $this->assertTrue($hasFields);
    $hasFields = Schema::hasFields('contacts', ['my_favorite_color', 'full_name', 'internal_notes', 'xxxx']);
    $this->assertFalse($hasFields);
    
});

it('should not be able to delete an index that does not exist', function () {
    $deleted = Schema::deleteIfExists('contactz');
    $this->assertFalse($deleted);
    try {
        Schema::delete('contactxxxz');
        $this->assertTrue(false);
    } catch (Exception $e) {
        $this->assertTrue(true);
    }
    
});

it('should clean up contacts index', function () {
    $deleted = Schema::deleteIfExists('contacts');
    $this->assertTrue($deleted);
    $this->assertFalse(Schema::hasIndex('contacts'));
});