## Neo4j Bolt PHP

## ⚠️ Warning ⚠️

This is a legacy api. This project exists to ️allow people to quickly get up and running with newer versions of neo4j in existing projects. For the newest drivers and latest features, please visit the [neo4j php client](https://github.com/neo4j-php/neo4j-php-client) on the [neo4j-php](https://github.com/neo4j-php) github page. There is also a lower level driver available under the name [Bolt](https://github.com/neo4j-php/Bolt)

### Requirements:

* PHP ^7.0 || ^8.0
* Neo4j ^3.0 || ^4.0
* `sockets` extension
* `bcmath` extension
* `mbstring` extension

### Installation

Require the package in your dependencies :

```bash
composer require laudis/graphaware-neo4j-bolt-legacy
```

### Setting up a driver and creating a session

```php

use GraphAware\Bolt\GraphDatabase;

$driver = GraphDatabase::driver("bolt://localhost");
$session = $driver->session();
```

### Sending a Cypher statement

```php
$session = $driver->session();
$session->run("CREATE (n)");
$session->close();

// with parameters :

$session->run("CREATE (n) SET n += {props}", ['name' => 'Mike', 'age' => 27]);
```

### Empty Arrays

Due to lack of Collections types in php, there is no way to distinguish when an empty array
should be treated as equivalent Java List or Map types.

Therefore you can use a wrapper around arrays for type safety :

```php
use GraphAware\Common\Collections;

        $query = 'MERGE (n:User {id: {id} }) 
        WITH n
        UNWIND {friends} AS friend
        MERGE (f:User {id: friend.name})
        MERGE (f)-[:KNOWS]->(n)';

        $params = ['id' => 'me', 'friends' => Collections::asList([])];
        $this->getSession()->run($query, $params);
        
// Or

        $query = 'MERGE (n:User {id: {id} }) 
        WITH n
        UNWIND {friends}.users AS friend
        MERGE (f:User {id: friend.name})
        MERGE (f)-[:KNOWS]->(n)';

        $params = ['id' => 'me', 'friends' => Collections::asMap([])];
        $this->getSession()->run($query, $params);

```

### TLS Encryption

In order to enable TLS support, you need to set the configuration option to `REQUIRED`, here an example :

```php
$config = \GraphAware\Bolt\Configuration::newInstance()
    ->withCredentials('bolttest', 'L7n7SfTSj0e6U')
    ->withTLSMode(\GraphAware\Bolt\Configuration::TLSMODE_REQUIRED);

$driver = \GraphAware\Bolt\GraphDatabase::driver('bolt://hobomjfhocgbkeenl.dbs.graphenedb.com:24786', $config);
$session = $driver->session();
```