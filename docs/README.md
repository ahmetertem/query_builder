# Query Builder

## Installation

### Install via Composer

```
composer require ahmetertem/query_builder
```

If you didn't included autoload file yet you need to include Composer's generated `autoload.php` file first.

```
require_once 'vendor/autoload.php';
```

### Manual Installation

You may download zip from [here](https://github.com/ahmetertem/query_builder/archive/master.zip) and locate anywhere in your project.
```
require_once 'your/path/src/qb.php';
```

## Getting Started
After installation you need to call library with namespace for short usage files which you'll use query_builder via

```
use \ahmetertem\qb;
```

For example:

```
use \ahmetertem\qb;
$qb = new qb();
```

If you do not want to short usage you may use like;

```
$qb = new \ahmetertem\qb();
```

> **Not**: If you do not use with `use` keyword ***you must*** enter long namespace each time.
> See: [or](#or) section
---

## Support

If you need help using **query_builder**, or have found a bug, please create an issue on the <a href="https://github.com/ahmetertem/query_builder/issues">GitHub repo</a>.

---
## Configuration

You may set some (*1 for now*) configuration statically.


### $default_limit

Default = 100

Default limit is for to the setting default [getSelect](!Index/getSelect), [getUpdate](!Index/getUpdate) and [getDelete](!Index/getDelete) limit.

**Not:** For no limitation you may use 0 (*zero*) (**not recommended**).

```
qb::$default_limit = 250;
```
---
## Examples
Writing `select` query is very simple with **qb**. You just need to create and object and give parameters.

### Basic Select Query

**Example**

```
$qb = new qb();
$qb->table('users');
echo($qb->getSelect());
```

**Output:**

```
select * from users limit 100
```

> qb will use [$default_limit](!Configuration) if you do not specify a limit. Default `$default_limit` is 100.

### and

**Example**

```
$qb = new qb();
$qb->table('users')
	->where('activated', 1)
	->where('name like "%amad%"');
echo($qb->getSelect());
```

**Output:**

```
select * from users where activated = 1 and name like "%amad%" limit 100
```


### or

You may use one or more condition in same time in `or` function. It'll concat your ors in paranthesis.

**Example #1**

```
$qb = new qb();
$qb->table('users')
	->where('activated', 1)
	->whereOr(qb::c('gender', 1), qb::c('gender', 0));
echo($qb->getSelect());
```

**Output:**

```
select * from users where activated = 1 and (gender = 1 or gender = 0) limit 100
```



**Example #2**

```
$qb = new qb();
$qb->table('users')
	->where('activated', 1)
	->where('name like "%amad%"')
	->whereOr(qb::c('gender', 1), qb::c('gender', 0))
	->whereOr(qb::c('is_administrator', 1), qb::c('is_accountant', 1));
echo($qb->getSelect());
```

**Output:**

```
select * from users where activated = 1 and name like "%amad%" and (gender = 1 or gender = 0) and (is_administrator = 1 or is_accountant = 1) limit 100
```

### specify read fields

**Example**

```
$qb = new qb();
$qb->table('users')
	->select('name')
	->select('surname')
	->select('gender, is_administrator, is_accountant'); // or chain them
echo($qb->getSelect());
```

**Output:**

```
select name, surname, gender, is_administrator, is_accountant from users limit 100
```

### group

**Example**

```
$qb = new qb();
$qb->table('users as u')
	->table('user_informations as ui')
	->where('ui.user_id', 'u.id')
	->select('u.*, ui.phone')
	->groupBy('u.id');
echo($qb->getSelect());
```

**Output:**

```
select u.*, ui.phone from users as u, user_informations as ui where ui.user_id = u.id group by u.id limit 100
```
