<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireAuth();
json_out(News::latest(16));
