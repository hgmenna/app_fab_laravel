<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Límites de categorías del Ranking Nacional
    |--------------------------------------------------------------------------
    |
    | MASTER_MAX_RG:
    | Última posición del Ranking General correspondiente a Master.
    |
    | NATIONAL_MAX_RG:
    | Última posición correspondiente a Nacional durante el desarrollo
    | normal de la temporada y luego del Campeonato Argentino.
    |
    | NATIONAL_MAX_RG_AFTER_STAGE_4:
    | Última posición Nacional al finalizar la Etapa 4, antes de incorporar
    | Campeón y Subcampeón del Campeonato Argentino.
    |
    */

    'master_max_rg' => 16,

    'national_max_rg' => 48,

    'national_max_rg_after_stage_4' => 46,

    'promotion_map' => [
        'S' => 'P',
        'T' => 'S',
        'PR' => 'T',
    ],

    'temporary_ranking_categories' => [
        'M',
        'N',
    ],

    'permanent_affiliation_category_for_temporary' => 'P',

];