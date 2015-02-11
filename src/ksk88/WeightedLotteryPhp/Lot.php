<?php

namespace ksk88\WeightedLotteryPhp;

class Lot
{
    const DEFAULT_LOTTERY_PRECISION = 1000;

    private $LotteryPrecision;

    private $NameIdColumn = 'ticket_id';

    function __construct($lottery_precision = null) {
        $this->LotteryPrecision = is_null($lottery_precision) ? self::DEFAULT_LOTTERY_PRECISION : $lottery_precision;
    }

    /**
     * Get tickets by weighted lottery
     *
     * @param array  $tickets   e.g. array( array('ticket_id'=>1, 'weight'=>10, 'layer'=>5), array('ticket_id'=>2, 'weight'=>20, 'layer'=>99) )
     *                          [ticket_id] -> Optional. But ID equivalent of column should be set for verification.
     *                          [weight]    -> Optional. You can use order as weight by setting. Column name is alterable.
     *                          [layer]     -> Optional. Set if you need to cut back by value. Column name is alterable.
     * @param int    $limit     e.g. 5
     *                          -> How many winners?
     * @param array  $setting   e.g. array( 'weight_gradient'=>2, 'use_order_as_weight'=>false, 'name_weight_source'=>'weight', 'threshold_conditions' => array( array('name'=>'layer', 'val'=>10, 'sign'=>'greater_or_equal') ), 'excluded_tickets'=>array(1,2) )
     *                          [weight_gradient]      -> Optional. You can adjust the probability difference between max and min.
     *                          [use_order_as_weight]  -> Optional. You can use order as weight.
     *                          [name_weight_source]   -> Optional. Default is 'weight'. Weight is taken by this name as key .
     *                          [threshold_conditions] -> Optional.
     *                                                    [name] -> Required. Name of key with a value to cut back.
     *                                                    [val]  -> Required. Threshold value.
     *                                                    [sign] -> Optional. Set from the following four types. Default is 'greater_or_equal'.
     *                                                              [lesser] -> '<'
     *                                                              [lesser_or_equal] -> '<='
     *                                                              [greater] -> '>'
     *                                                              [greater_or_equal] -> '>='
     *                          [name_id]              -> Optional. Use when exclude tickets. Default is 'ticket_id'.
     *                          [excluded_tickets]     -> Optional. Exclude tickets by ID.
     * @return array
     */
    public function pickFromWeightedLottery($tickets, $limit = 1, $setting = array())
    {
        // Lottery setting
        $weight_gradient = null;
        if (isset($setting['weight_gradient'])) {
            $weight_gradient = max($setting['weight_gradient'], 1);
        }
        $use_order_as_weight = empty($setting['use_order_as_weight']) || !$setting['use_order_as_weight'] ? false : true;
        $name_weight_source = empty($setting['name_weight_source']) ? 'weight' : $setting['name_weight_source'];
        $threshold_conditions = empty($setting['threshold_conditions']) ? array() : $setting['threshold_conditions'];
        $excluded_tickets = empty($setting['excluded_tickets']) ? array() : $setting['excluded_tickets'];
        $this->NameIdColumn = empty($setting['name_id']) ? $this->NameIdColumn : $setting['name_id'];

            // Filter by thresholds
        $tickets = $this->filterTicketsByConditions($tickets, $threshold_conditions, $excluded_tickets);

        // Add weight for weighted lottery
        $tickets = $this->addLotteryWeight($tickets, $name_weight_source, $weight_gradient, $use_order_as_weight);

        // Drawing
        $picks = $this->drawTicketsWithWeight($tickets, $limit);

        return $picks;
    }

    public function shuffleWeighted($tickets, $setting)
    {
        // Return all tickets after weighted shuffle.
        return $this->pickFromWeightedLottery($tickets, count($tickets), $setting);
    }

    private function filterTicketsByConditions($tickets, $threshold_conditions, $excluded_tickets)
    {
        if (empty($threshold_conditions) && empty($excluded_tickets)) {
            return $tickets;
        }

        $excluded_ticket_ids = array_flip($excluded_tickets);

        foreach ($tickets as $key => $ticket) {
            if (!$this->isApprovedTicket($ticket, $threshold_conditions, $excluded_ticket_ids)) {
                unset($tickets[$key]);
            }
        }
        return $tickets;
    }

    private function isApprovedTicket($ticket, $threshold_conditions, $excluded_ticket_ids)
    {
        $ticket_id = $ticket[$this->NameIdColumn];
        if (array_key_exists($ticket_id, $excluded_ticket_ids)) {
            return false;
        }

        foreach ($threshold_conditions as $condition) {
            if (!isset($condition['name']) || !isset($condition['val'])) {
                break;  // Skip check
            }
            $name = $condition['name'];
            $val = $condition['val'];
            if (!isset($ticket[$name]) || !$this->implementConditionSign($condition, $ticket[$name], $val)) {
                return false;
            }
        }
        return true;
    }

    private function implementConditionSign($condition, $ticket_val, $condition_val)
    {
        $sign = !isset($condition['sign']) ? 'greater_or_equal' : $condition['sign'] ;
        switch ($sign) {
            case 'lesser':
                return $ticket_val < $condition_val;

            case 'lesser_or_equal':
                return $ticket_val <= $condition_val;

            case 'greater':
                return $ticket_val > $condition_val;

            case 'greater_or_equal':
            default:
                return $ticket_val >= $condition_val;
        }
    }

    private function addLotteryWeight($tickets, $name_weight_source, $weight_gradient, $use_order_as_weight)
    {
        if ($use_order_as_weight) {
            $tickets = $this->appendWeightByOrder($tickets, $name_weight_source);
        }

        $total_weight = 0;
        $min_weight = null;  // Not less than zero
        $max_weight = null;  // Not less than zero
        foreach ($tickets as $ticket) {
            $weight = $this->getWeightByNameWeightOrigin($ticket, $name_weight_source);
            $total_weight += $weight;
            if ($weight > 0 && (is_null($min_weight) || $weight < $min_weight)) {
                $min_weight = $weight;
            }
            if ($weight > 0 && (is_null($max_weight) || $weight > $max_weight)) {
                $max_weight = $weight;
            }
        }

        $is_forced_flat = !is_null($weight_gradient) && $weight_gradient == 1 ? true : false;
        $use_jackup_weight = !is_null($weight_gradient) && $weight_gradient != 1 ? true : false;

        $jackup_weight = 0;
        if (!is_null($min_weight) && !is_null($max_weight) && $use_jackup_weight && ($min_weight != $max_weight)) {
            $jackup_weight = ($max_weight - ($weight_gradient * $min_weight)) / ($weight_gradient - 1);
        }

        $adjuster_to_int = 1;
        if (!is_null($max_weight)) {
            $max_weight_with_jackup = $max_weight + $jackup_weight;
            $adjuster_to_int = $this->LotteryPrecision / $max_weight_with_jackup;
        }

        foreach ($tickets as $key => $ticket) {
            $weight = $this->getWeightByNameWeightOrigin($ticket, $name_weight_source);
            $lottery_weight = 0;
            if ($weight > 0) {
                $lottery_weight = 1;
                if (!$is_forced_flat) {
                    $lottery_weight = round(($weight + $jackup_weight) * $adjuster_to_int);
                    if ($lottery_weight < 1) {
                        $lottery_weight = 1;    // Guaranteed min weight
                    }
                }
            }
            $tickets[$key]['lottery_weight'] = $lottery_weight;
        }

        return $tickets;
    }

    private function appendWeightByOrder($tickets, $name_weight_source)
    {
        $weight = count($tickets);
        foreach ($tickets as $key => $ticket_info) {
            $tickets[$key][$name_weight_source] = $weight;
            $weight --;
        }
        return $tickets;
    }

    private function getWeightByNameWeightOrigin($ticket, $name_weight_source)
    {
        $weight = 0;
        if (!empty($ticket[$name_weight_source])) {
            $weight = $ticket[$name_weight_source];
            if ($weight < 0) {
                $weight = 0;
            }
        }
        return $weight;
    }

    private function drawTicketsWithWeight($tickets, $limit)
    {
        if (empty($tickets) || count($tickets) <= 1) {
            return $tickets;
        }

        $limit = min($limit, count($tickets));

        $winning_tickets = array();
        for ($i = 0; $i < $limit; $i ++) {
            $ret_lot = $this->lotWithWeight($tickets);
            $winning_tickets[] = $ret_lot['winner'];
            $tickets = $ret_lot['others'];
        }

        return $winning_tickets;
    }

    private function lotWithWeight($tickets)
    {
        $total_ticket = count($tickets);
        $total_weight = 0;
        foreach ($tickets as $ticket) {
            $total_weight += $ticket['lottery_weight'];
        }

        $lottery_number = rand(1, $total_weight);
        $heap_weight = 0;
        $winning_ticket = array();
        $index = 0;
        foreach ($tickets as $key => $ticket) {
            $index ++;
            $heap_weight += $ticket['lottery_weight'];
            if ($index >= $total_ticket || $lottery_number <= $heap_weight) {
                $winning_ticket = $ticket;
                unset($tickets[$key]);
                break;
            }
        }

        return array(
            'winner' => $winning_ticket,
            'others' => $tickets,
        );
    }
}
