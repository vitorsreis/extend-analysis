<?php

/**
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

namespace VSR\Extend\Analysis\Contract;

use VSR\Extend\Analysis;

abstract class AbstractChart
{
    /**
     * @param string[] $group c=current, s=second, s10=10 second, i=minute, i10=minute, h=hour, d=day, m=month, y=year
     * @param array{id:string, value:mixed} ...$data
     * @return bool
     */
    public static function chart($group = ['s10', 'i', 'i10', 'h', 'd'], ...$data)
    {
        $hits = [];

        $now = date('YmdHis');
        foreach ($data as $i) {
            in_array('c', $group) && $hits[] = [
                'id' => "avg_c-$i[id]", # Current, no group
                'value' => $i['value']
            ];
            in_array('s', $group) && $hits[] = [
                'id' => "avg_s-$now-$i[id]", # Per Second
                'value' => $i['value']
            ];
            in_array('s10', $group) && $hits[] = [
                'id' => 'avg_s10-' . substr($now, 0, -1) . "-$i[id]", # Per 10 Seconds
                'value' => $i['value']
            ];
            in_array('i', $group) && $hits[] = [
                'id' => 'avg_i-' . substr($now, 0, -2) . "-$i[id]", # Per Minute
                'value' => $i['value']
            ];
            in_array('i10', $group) && $hits[] = [
                'id' => 'avg_i10-' . substr($now, 0, -3) . "-$i[id]", # Per 10 Minutes
                'value' => $i['value']
            ];
            in_array('h', $group) && $hits[] = [
                'id' => 'avg_h-' . substr($now, 0, -4) . "-$i[id]", # Per Hour
                'value' => $i['value']
            ];
            in_array('d', $group) && $hits[] = [
                'id' => 'avg_d-' . substr($now, 0, -6) . "-$i[id]", # Per Day
                'value' => $i['value']
            ];
            in_array('m', $group) && $hits[] = [
                'id' => 'avg_m-' . substr($now, 0, -8) . "-$i[id]", # Per Month
                'value' => $i['value']
            ];
            in_array('y', $group) && $hits[] = [
                'id' => 'avg_y-' . substr($now, 0, -10) . "-$i[id]", # Per Year
                'value' => $i['value']
            ];
        }

        return $hits && Analysis::getDriver()->avg('chart', ...$hits);
    }
}
