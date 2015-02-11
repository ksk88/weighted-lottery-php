weighted-lottery-php
=============

PHP library

# Example

test.php
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
            array('weight' => 5, 'label' => 'rare'),
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

```sh
$ php test.php
array (
  'normal_1' => 1962,
  'normal_2' => 1986,
  'normal_3' => 1990,
  'normal_4' => 1915,
  'normal_5' => 1901,
  'rare' => 200,
  'super_rare' => 46,
)
```
