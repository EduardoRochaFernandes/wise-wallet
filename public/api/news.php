<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
json_out(MarketNews::latest(16));
