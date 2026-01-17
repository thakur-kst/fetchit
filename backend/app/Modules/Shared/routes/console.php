<?php

use Illuminate\Support\Facades\Schedule;

// Prune Telescope data older than 48 hours daily
Schedule::command('telescope:prune --hours=48')->daily();