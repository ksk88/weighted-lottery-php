weighted-lottery-php
=============

weighted-lottery-php provides a not equality shuffle function rather than a just shuffle function. Determine is based on each choice's proportion of the total weight.

# Installation

To install weighted-lottery-php with Composer, just add the following to your composer.json file:

```js
// composer.json
{
    "require-dev": {
        "ksk88/weighted-lottery-php": "dev-master"
    }
}
```

Then, you can install the new dependencies by running Composer’s update command from the directory where your `composer.json` file is located:

```sh
# install
$ php composer.phar install --dev
# update
$ php composer.phar update ksk88/weighted-lottery-php --dev

# or you can simply execute composer command if you set it to
# your PATH environment variable
$ composer install --dev
$ composer update ksk88/weighted-lottery-php --dev
```

You can see this library on [Packagist](https://packagist.org/packages/ksk88/weighted-lottery-php).

Composer installs autoloader at `./vendor/autoloader.php`. If you use weighted-lottery-php in your php script, add:

```php
require_once 'vendor/autoload.php';
```

If you use Symfony2, autoloader has to be detected automatically.

Or you can use git clone command:

```sh
# HTTP
$ git clone https://github.com/ksk88/weighted-lottery-php.git
# SSH
$ git clone git@github.com:ksk88/weighted-lottery-php.git
```

# Example

[1] Create test function. (test.php)

```php
<?php

require_once './vendor/autoload.php';

use ksk88\WeightedLotteryPhp\Lot;

class Draw
{
    public function exec()
    {
        $results = array();

        for ($i = 0; $i < 10000; $i++) {

            // Lottery
            $winner = $this->pick();

            // Count
            $count = 0;
            $label = $winner['label'];
            if (array_key_exists($label, $results)) {
                $count = $results[$label];
            }
            $results[$label] = $count + 1;

        }

        var_export($results);
    }

    private function pick()
    {
        $tickets = array(
            array('weight' => 1,  'label' => 'super_rare'),
            array('weight' => 5,  'label' => 'rare'),
            array('weight' => 50, 'label' => 'normal_1'),
            array('weight' => 50, 'label' => 'normal_2'),
            array('weight' => 50, 'label' => 'normal_3'),
            array('weight' => 50, 'label' => 'normal_4'),
            array('weight' => 50, 'label' => 'normal_5'),
        );

        $lot = new Lot();
        $num_picked = 1;
        $winners = $lot->pickFromWeightedLottery($tickets, $num_picked);

        $winner = reset($winners);

        return $winner;
    }
}

$draw = new Draw();
$draw->exec();
```

[2] Execute test.php

```sh
# execute
$ php test.php
array (
  'super_rare' => 46,
  'rare'       => 200,
  'normal_1'   => 1962,
  'normal_2'   => 1986,
  'normal_3'   => 1990,
  'normal_4'   => 1915,
  'normal_5'   => 1901,
)
```

[3] Summary count

LABEL | WEIGHT | COUNT
:-------------:|-------------:|-------------:
super_rare | 1 | 46
rare | 5 | 200
normal_1 | 50 | 1,962
normal_2 | 50 | 1,986
normal_3 | 50 | 1,990
normal_4 | 50 | 1,915
normal_5 | 50 | 1,901

**Result is random, but based on each choice's proportion of the total weight.**

# Options

The library has several options. You can easily manipulate your array using options.

*weight_gradient
 *Difference frequency of occurrence between the top and bottom.
 *So when set '1', the function will operate as normal shuffle.
 *Default is null.

*use_order_as_weight
 *Use order as the weight of lottery.
 *Default is false.

*name_weight_source
 *Weight is taken by this name as key.
 *Default is 'weight'.

*threshold_conditions
 *Cut back tickets by any conditions.
 *Default is null.

*name_id
 *Use when exclude tickets.
 *Default is 'ticket_id'.

*excluded_tickets
 *Exclude tickets by ID.
 *ID is taken on the basis of 'name_id' as key.
 *Default is array().

## Usage

* test2.php
```php
<?php
require_once './vendor/autoload.php';
use ksk88\WeightedLotteryPhp\Lot;

$results = summary();
ksort($results);
var_dump($results);

function draw() {
    $tickets = array(
        array('rank' => 1),
        array('rank' => 2),
        array('rank' => 3),
        array('rank' => 4),
        array('rank' => 5),
        array('rank' => 6),
        array('rank' => 7),
        array('rank' => 8),
        array('rank' => 9),
        array('rank' => 10),
    );

    $setting = array(
        'weight_gradient' => 2,
        'use_order_as_weight' => true,
        'threshold_conditions' => array(
            array('name' => 'rank', 'val' => 9,  'sign' => 'lesser_or_equal'),
        ),
    );

    $lot = new Lot();
    $num_picked = 1;
    $winners = $lot->pickFromWeightedLottery($tickets, $num_picked, $setting);

    $winner = reset($winners);
    return $winner;
}

function summary() {
    $results = array();
    for ($i = 0; $i < 10000; $i++) {

        // Lottery
        $winner = draw();

        // Count
        $count = 0;
        $label = $winner['rank'];
        if (array_key_exists($label, $results)) {
            $count = $results[$label];
        }
        $results[$label] = $count + 1;
    }
    return $results;
}
```

* Result

RANK | COUNT
:-------------:|-------------:
1 | 1,492
2 | 1,398
3 | 1,318
4 | 1,189
5 | 1,070
6 | 1,038
7 | 939
8 | 829
9 | 727
10 | *Removed by option

**The difference frequency of occurrence between the top and bottom is roughly doubled by options.**