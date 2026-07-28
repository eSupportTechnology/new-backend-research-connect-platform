<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gold membership unlocks PAID research
    |--------------------------------------------------------------------------
    |
    | When true, a Gold member reads PAID research papers without paying the
    | publisher price — the membership itself is the entitlement. When false,
    | every tier (Bronze, Silver, Gold, School Student) must buy the paper
    | individually, which is what the client's written specification asks for.
    |
    | This is the ONE switch that decides it. Nothing else in the codebase may
    | compare a tier against a PAID paper.
    |
    */

    'gold_unlocks_paid' => (bool) env('RESEARCH_GOLD_UNLOCKS_PAID', true),

    /*
    |--------------------------------------------------------------------------
    | Minimum tier for LIMITED ACCESS research
    |--------------------------------------------------------------------------
    |
    | Bronze accounts are below this, so they are told to upgrade. School
    | Student accounts bypass it entirely (see ResearchAccessService).
    |
    */

    'limited_access_tier' => 'silver',

];