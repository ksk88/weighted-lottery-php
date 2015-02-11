<?php

namespace ksk88\WeightedLotteryPhp\Tests;

use ksk88\WeightedLotteryPhp\Lot;

class DrawTest extends \PHPUnit_Framework_TestCase
{
    const NUM_LARGE_ENOUGH = 5000;
    const NUM_FOR_PICK_SEQUENTIAL = 2;
    const LABEL_NORMAL = 'normal';
    const LABEL_RARE = 'rare';
    const LABEL_SUPER_RARE = 'super_rare';
    const LABEL_REMOVED_1 = 'removed_1';
    const LABEL_REMOVED_2 = 'removed_2';
    const LABEL_REMOVED_3 = 'removed_3';
    const LABEL_REMOVED_4 = 'removed_4';

    public function testPick()
    {
        $results = array();

        for ($i = 0; $i < self::NUM_LARGE_ENOUGH; $i++) {

            // Lottery
            $winners = $this->pick();
            $this->assertEquals(1, count($winners));
            $winner = reset($winners);

            // Count
            $count = 0;
            $label = $winner['label'];
            if (array_key_exists($label, $results)) {
                $count = $results[$label];
            }
            $results[$label] = $count + 1;

        }

        $this->assertGreaterThan($results[self::LABEL_SUPER_RARE], $results[self::LABEL_RARE]);
        $this->assertGreaterThan($results[self::LABEL_RARE], $results[self::LABEL_NORMAL]);
    }

    private function pick()
    {
        $tickets = array(
            array('weight' => 1,  'label' => self::LABEL_SUPER_RARE),
            array('weight' => 10, 'label' => self::LABEL_RARE),
            array('weight' => 89, 'label' => self::LABEL_NORMAL),
        );

        $lot = new Lot();
        $winner = $lot->pickFromWeightedLottery($tickets);

        return $winner;
    }

    public function testPickSequential()
    {
        $results = array();

        for ($i = 0; $i < self::NUM_LARGE_ENOUGH; $i++) {

            // Lottery
            $winners = $this->pickSequential();
            $this->assertEquals(self::NUM_FOR_PICK_SEQUENTIAL, count($winners));

            $labels_for_check_duplication = array();
            foreach ($winners as $winner) {
                // Count
                $count = 0;
                $label = $winner['label'];
                if (array_key_exists($label, $results)) {
                    $count = $results[$label];
                }
                $results[$label] = $count + 1;

                // Check duplication
                $this->assertFalse(array_key_exists($label, $labels_for_check_duplication));
                $labels_for_check_duplication[$label] = true;
            }
        }

        $this->assertGreaterThan($results[self::LABEL_SUPER_RARE], $results[self::LABEL_RARE]);
        $this->assertGreaterThan($results[self::LABEL_RARE], $results[self::LABEL_NORMAL]);
    }

    private function pickSequential()
    {
        $tickets = array(
            array('weight' => 1,  'label' => self::LABEL_SUPER_RARE),
            array('weight' => 10, 'label' => self::LABEL_RARE),
            array('weight' => 89, 'label' => self::LABEL_NORMAL),
        );

        $lot = new Lot();
        $winners = $lot->pickFromWeightedLottery($tickets, self::NUM_FOR_PICK_SEQUENTIAL);

        return $winners;
    }

    public function testPickWithSetting()
    {
        $results = array();

        for ($i = 0; $i < self::NUM_LARGE_ENOUGH; $i++) {

            // Lottery
            $winners = $this->pickWithSetting();
            $this->assertEquals(1, count($winners));
            $winner = reset($winners);

            // Check removed
            $this->assertNotEquals(self::LABEL_REMOVED_1, $winner['label']);
            $this->assertNotEquals(self::LABEL_REMOVED_2, $winner['label']);
            $this->assertNotEquals(self::LABEL_REMOVED_3, $winner['label']);
            $this->assertNotEquals(self::LABEL_REMOVED_4, $winner['label']);

            // Count
            $count = 0;
            $label = $winner['label'];
            if (array_key_exists($label, $results)) {
                $count = $results[$label];
            }
            $results[$label] = $count + 1;

        }

        $this->assertGreaterThan($results[self::LABEL_SUPER_RARE], $results[self::LABEL_RARE]);
        $this->assertGreaterThan($results[self::LABEL_RARE], $results[self::LABEL_NORMAL]);
    }

    private function pickWithSetting()
    {
        $tickets = array(
            array('custom_weight' => 2,   'label' => self::LABEL_SUPER_RARE, 'layer' => '1', 'score' => '100'),
            array('custom_weight' => 10,  'label' => self::LABEL_RARE,       'layer' => '1', 'score' => '90'),
            array('custom_weight' => 88,  'label' => self::LABEL_NORMAL,     'layer' => '2', 'score' => '80'),
            array('custom_weight' => 999, 'label' => self::LABEL_REMOVED_1,  'layer' => '2', 'score' => '70'),
            array('custom_weight' => 999, 'label' => self::LABEL_REMOVED_2,  'layer' => '3', 'score' => '60'),
            array('custom_weight' => 2,   'label' => self::LABEL_REMOVED_3,  'layer' => '1', 'score' => '100'),
            array('custom_weight' => 10,  'label' => self::LABEL_REMOVED_4,  'layer' => '1', 'score' => '90'),
        );

        $setting = array(
            'weight_gradient' => 100,
            'use_order_as_weight' => false,
            'name_weight_source' => 'custom_weight',
            'threshold_conditions' => array(
                array('name' => 'layer', 'val' => 2,  'sign' => 'lesser_or_equal'),
                array('name' => 'score', 'val' => 80, 'sign' => 'greater_or_equal'),
            ),
            'name_id' => 'label',
            'excluded_tickets' => array(self::LABEL_REMOVED_3, self::LABEL_REMOVED_4),
        );

        $lot = new Lot();
        $winner = $lot->pickFromWeightedLottery($tickets, 1, $setting);

        return $winner;
    }
}