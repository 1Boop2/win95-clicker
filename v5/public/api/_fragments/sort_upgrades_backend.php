<?php
// Paste this AFTER you have built $shop array with 'next_cost' for each item.
usort($shop, function($a, $b) {
  $ca = isset($a['next_cost']) ? (float)$a['next_cost'] : PHP_FLOAT_MAX;
  $cb = isset($b['next_cost']) ? (float)$b['next_cost'] : PHP_FLOAT_MAX;
  if ($ca == $cb) return strcmp($a['code'] ?? '', $b['code'] ?? '');
  return $ca <=> $cb;
});
