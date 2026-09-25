<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sentinelops:cleanup-evidence-staging')->hourly();
